<?php

namespace App\Services\Normalization;

use App\Models\MasterProduct;
use App\Models\MasterProductSupplier;
use App\Models\NormalizedProduct;
use App\Services\Categories\CategoryPathBuilderService;

class MasterProductLinkingService
{
    public function __construct(
        protected CategoryPathBuilderService $pathBuilder,
    ) {}

    /**
     * Crea o vincula master_products desde normalized_products.
     * master_products.quantity no se copia de normalized (queda 0 o valor operativo aparte).
     */
    public function linkOrCreateForProducts(array $normalizedProductIds): array
    {
        $created = 0;
        $linked = 0;
        $products = NormalizedProduct::whereIn('id', $normalizedProductIds)->get();

        foreach ($products as $np) {
            if ($np->master_product_id !== null) {
                $this->ensureMasterProductSupplier($np);
                continue;
            }

            $master = $this->findMasterByEan($np->ean13);
            if ($master !== null) {
                $np->master_product_id = $master->id;
                $np->save();
                $this->ensureMasterProductSupplier($np);
                $linked++;
                continue;
            }

            $master = $this->createMasterFromNormalized($np);
            if ($master !== null) {
                $np->master_product_id = $master->id;
                $np->save();
                $this->ensureMasterProductSupplier($np);
                $created++;
            }
        }

        return ['created' => $created, 'linked' => $linked, 'total' => $products->count()];
    }

    protected function findMasterByEan(?string $ean): ?MasterProduct
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

    protected function createMasterFromNormalized(NormalizedProduct $np): ?MasterProduct
    {
        $ean = $np->ean13 !== null && $np->ean13 !== '' ? preg_replace('/\D/', '', $np->ean13) : null;
        if ($ean !== null && strlen($ean) !== 13) {
            $ean = null;
        }

        $name = trim($np->name ?? '');
        if ($name === '') {
            return null;
        }

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
            'category_id' => $np->category_id,
            'category_status' => $np->category_status ?: ($np->category_id ? 'suggested' : 'unassigned'),
            'category_path_export' => $np->category_id
                ? $this->pathBuilder->buildExportPath($np->category_id)
                : ($np->category_path_export ?: null),
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

    protected function ensureMasterProductSupplier(NormalizedProduct $np): void
    {
        if ($np->master_product_id === null) {
            return;
        }
        MasterProductSupplier::firstOrCreate(
            [
                'master_product_id' => $np->master_product_id,
                'normalized_product_id' => $np->id,
            ],
            [
                'supplier_id' => $np->supplier_id,
                'supplier_reference' => $np->supplier_reference,
                'is_primary' => false,
            ]
        );
    }
}
