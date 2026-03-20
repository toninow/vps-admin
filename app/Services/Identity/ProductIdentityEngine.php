<?php

namespace App\Services\Identity;

use App\Models\MasterProduct;
use App\Models\MasterProductSupplier;
use App\Models\NormalizedProduct;

class ProductIdentityEngine
{
    /**
     * Resuelve a qué master_product pertenece un normalized_product,
     * aplicando reglas de identidad fuertes y registrando la decisión.
     */
    public function resolveMasterProduct(NormalizedProduct $product): MasterProduct
    {
        // 1. Coincidencia fuerte por EAN
        $eanMaster = $this->findByValidEan($product->ean13);
        if ($eanMaster) {
            return $this->linkAndRecord($product, $eanMaster, 'ean', 1.0);
        }

        // 2. Coincidencia por marca + referencia proveedor (exacta)
        $brand = $this->normalizeText($product->brand);
        $reference = $this->normalizeText($product->supplier_reference);
        if ($brand !== '' && $reference !== '') {
            $refMaster = MasterProduct::whereRaw('LOWER(brand) = ?', [$brand])
                ->whereRaw('LOWER(reference) = ?', [$reference])
                ->first();

            if ($refMaster) {
                return $this->linkAndRecord($product, $refMaster, 'reference', 0.95);
            }
        }

        // 3. Crear nuevo master_product.
        // La similitud difusa por nombre se desactiva en la ruta automatica:
        // a esta escala resulta costosa y puede fusionar productos distintos.
        $newMaster = $this->createMasterFromNormalized($product);

        return $this->linkAndRecord($product, $newMaster, 'created', null);
    }

    protected function findByValidEan(?string $ean): ?MasterProduct
    {
        if ($ean === null || trim($ean) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $ean);
        if (strlen($digits) !== 13) {
            return null;
        }

        return MasterProduct::where('ean13', $digits)->first();
    }

    protected function normalizeText(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $value = mb_strtolower($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return $value;
    }

    protected function findByNameSimilarity(string $name, ?string $brandNormalized): ?MasterProduct
    {
        if ($brandNormalized === null || $brandNormalized === '') {
            return null;
        }

        $candidates = MasterProduct::query()
            ->whereRaw('LOWER(brand) = ?', [$brandNormalized])
            ->limit(500)
            ->get();
        $best = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $score = $this->nameSimilarityScore($name, $candidate->name);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        if ($best && $bestScore >= 0.85) {
            return $best;
        }

        return null;
    }

    protected function nameSimilarityScore(string $a, string $b): float
    {
        $aNorm = $this->normalizeText($a);
        $bNorm = $this->normalizeText($b);

        if ($aNorm === '' || $bNorm === '') {
            return 0.0;
        }

        similar_text($aNorm, $bNorm, $percent);

        return round($percent / 100, 4);
    }

    protected function isBrandCompatible(string $brandA, string $brandB): bool
    {
        if ($brandA === '' || $brandB === '') {
            // si no hay información suficiente de marca, no bloqueamos por marca
            return true;
        }

        return $brandA === $brandB;
    }

    protected function createMasterFromNormalized(NormalizedProduct $np): MasterProduct
    {
        $ean = $np->ean13 !== null && $np->ean13 !== '' ? preg_replace('/\D/', '', $np->ean13) : null;
        if ($ean !== null && strlen($ean) !== 13) {
            $ean = null;
        }

        $name = trim((string) $np->name);
        $reference = $np->supplier_reference ? trim($np->supplier_reference) : null;
        if ($reference === '') {
            $reference = null;
        }

        return MasterProduct::create([
            'ean13' => $ean,
            'seed_normalized_product_id' => $np->id,
            'reference' => $reference,
            'name' => $name,
            'summary' => $np->summary ?: null,
            'description' => $np->description ?: null,
            'quantity' => 0,
            'price_tax_incl' => $np->price_tax_incl,
            'cost_price' => $np->cost_price,
            'tax_rule_id' => $np->tax_rule_id ?? 1,
            'warehouse' => $np->warehouse ?? 'CARPETANA',
            'active' => (int) ($np->active ?? 1),
            'brand' => $np->brand ?: null,
            'category_id' => null,
            'category_path_export' => $np->category_path_export ?: null,
            'tags' => $np->tags ?: null,
            'search_keywords_normalized' => $this->buildSearchKeywords($np),
            'is_approved' => false,
            'version' => 1,
        ]);
    }

    protected function buildSearchKeywords(NormalizedProduct $np): string
    {
        $parts = array_filter([$np->name, $np->brand, $np->tags, $np->supplier_reference]);
        $text = implode(' ', $parts);
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    protected function linkAndRecord(NormalizedProduct $product, MasterProduct $master, string $type, ?float $score): MasterProduct
    {
        // reglas de seguridad: nunca fusionar si marcas o referencias proveedor claramente distintas
        if (! $this->isSafeMatch($product, $master, $type, $score)) {
            // en caso de duda, creamos un master nuevo en lugar de reutilizar
            $master = $this->createMasterFromNormalized($product);
            $type = 'created';
            $score = null;
        }

        $product->master_product_id = $master->id;
        $product->save();

        MasterProductSupplier::firstOrCreate(
            [
                'master_product_id' => $master->id,
                'normalized_product_id' => $product->id,
            ],
            [
                'supplier_id' => $product->supplier_id,
                'supplier_reference' => $product->supplier_reference,
                'is_primary' => false,
            ]
        );

        return $master;
    }

    protected function isSafeMatch(NormalizedProduct $product, MasterProduct $master, string $type, ?float $score): bool
    {
        // nunca aceptar si score de similitud por nombre por debajo del umbral
        if ($type === 'name_similarity') {
            if ($score === null || $score < 0.85) {
                return false;
            }
        }

        // marcas distintas y presentes en ambos lados -> no fusionar
        $npBrand = $this->normalizeText($product->brand);
        $mpBrand = $this->normalizeText($master->brand);
        if ($npBrand !== '' && $mpBrand !== '' && $npBrand !== $mpBrand) {
            return false;
        }

        // Un EAN válido debe poder consolidar aunque cada proveedor use
        // una referencia distinta para el mismo producto.
        if ($type === 'ean') {
            return true;
        }

        // referencias proveedor distintas y presentes en ambos lados -> no fusionar
        $npRef = $this->normalizeText($product->supplier_reference);
        $mpRef = $this->normalizeText($master->reference);
        if ($npRef !== '' && $mpRef !== '' && $npRef !== $mpRef) {
            return false;
        }

        return true;
    }
}
