<?php

namespace App\Services\Suppliers\Profiles;

class AdagioSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'adagio';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'upc', 'ean/upc', 'codigo barras', 'ean13', 'upc12',
        ]);
        $base['name'] = array_merge($base['name'], [
            'descripcion_breve_producto', 'descripcion breve producto', 'nombre producto',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'descripcion_breve_producto', 'descripcion breve producto',
        ]);
        $base['description'] = array_merge($base['description'], [
            'descripcion_ampliada', 'descripcion ampliada',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'fabricante',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'stock',
        ]);
        $base['price_tax_incl'] = array_merge([
            'pvp', 'pvr', 'precio venta recomendado', 'pvpr',
        ], $base['price_tax_incl'], [
            'precio_venta_tienda', 'precio venta tienda', 'precio venta',
        ]);
        $base['cost_price'] = array_merge([
            'precio_venta_tienda', 'precio venta tienda',
        ], $base['cost_price'], [
            'coste', 'precio_compra', 'precio compra',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'categoria_1', 'categoria_2', 'categoria_3', 'categoria 1', 'categoria 2', 'categoria 3',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'ref', 'referencia', 'codigo', 'codigo producto',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'imagen', 'url imagen', 'imagen producto',
        ]);

        return $base;
    }

    public function suggestMapping(\App\Models\Supplier $supplier, \App\Models\SupplierImport $import, array $columns, array $sampleRows): array
    {
        $mapping = parent::suggestMapping($supplier, $import, $columns, $sampleRows);

        $normalized = [];
        foreach ($columns as $column) {
            $normalized[$this->normalizeHeaderForMatching($column)] = $column;
        }

        foreach (['pvp', 'pvr'] as $preferredSaleColumn) {
            if (isset($normalized[$preferredSaleColumn])) {
                $mapping['price_tax_incl'] = $normalized[$preferredSaleColumn];
                break;
            }
        }

        foreach (['precio venta tienda', 'precio_venta_tienda'] as $preferredCostColumn) {
            if (isset($normalized[$preferredCostColumn])) {
                $mapping['cost_price'] = $normalized[$preferredCostColumn];
                break;
            }
        }

        $categoryColumns = [];
        foreach ([1, 2, 3] as $level) {
            foreach (["categoria {$level}", "categoria_{$level}"] as $candidate) {
                if (isset($normalized[$candidate])) {
                    $categoryColumns[] = $normalized[$candidate];
                    break;
                }
            }
        }

        if ($categoryColumns !== []) {
            $mapping['category_path_export'] = implode(' | ', array_values(array_unique($categoryColumns)));
        }

        return $mapping;
    }

    /**
     * ADAGIO mezcla EAN13 y UPC12; dar puntuación a ambos.
     */
    protected function scoreColumnByContent(string $target, string $columnName, array $sampleRows): float
    {
        if ($target === 'ean13') {
            $values = $this->extractColumnValues($columnName, $sampleRows);
            if ($values === []) {
                return 0.0;
            }
            $len13 = 0;
            $len12 = 0;
            $numericOnly = 0;
            foreach ($values as $v) {
                $digits = preg_replace('/\D/', '', $v);
                if ($digits === '') {
                    continue;
                }
                if ($digits === $v) {
                    $numericOnly++;
                }
                $len = strlen($digits);
                if ($len === 13) {
                    $len13++;
                } elseif ($len === 12) {
                    $len12++;
                }
            }
            $total = count($values);
            if ($total === 0) {
                return 0.0;
            }
            $score = ($numericOnly / $total) * 2.0;
            $score += ($len13 / $total) * 4.0;
            $score += ($len12 / $total) * 3.0; // UPC12 muy habitual en ADAGIO

            return $score;
        }

        return parent::scoreColumnByContent($target, $columnName, $sampleRows);
    }

    /**
     * @param  array<int, array<string, string>>  $sampleRows
     * @return string[]
     */
    private function extractColumnValues(string $columnName, array $sampleRows): array
    {
        $values = [];
        foreach ($sampleRows as $row) {
            if (array_key_exists($columnName, $row)) {
                $v = trim((string) $row[$columnName]);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
            if (count($values) >= 200) {
                break;
            }
        }

        return $values;
    }
}
