<?php

namespace App\Services\Suppliers\Profiles;

class VallestradeSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'vallestrade';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_values(array_unique(array_merge(
            ['nombre articulo', 'meta titulo'],
            array_diff($base['name'], ['descripcion', 'descripcion corta'])
        )));
        $base['summary'] = array_values(array_unique(array_merge(
            ['descripcion corta', 'meta descripcion', 'nombre articulo'],
            $base['summary']
        )));
        $base['description'] = array_values(array_unique(array_merge(
            ['descripcion larga', 'descripcion corta'],
            $base['description']
        )));
        $base['cost_price'] = array_values(array_unique(array_merge(
            ['neto'],
            $base['cost_price']
        )));
        $base['price_tax_incl'] = array_values(array_unique(array_merge(
            ['pvp + iva', 'pvp+iva'],
            array_diff($base['price_tax_incl'], ['pvp'])
        )));
        $base['category_path_export'] = array_values(array_unique(array_merge(
            ['familia', 'tipo producto'],
            $base['category_path_export']
        )));
        $base['image_urls'] = array_values(array_unique(array_merge(
            ['imagen 1', 'imagen_1'],
            $base['image_urls']
        )));

        return $base;
    }

    public function suggestMapping(\App\Models\Supplier $supplier, \App\Models\SupplierImport $import, array $columns, array $sampleRows): array
    {
        $mapping = parent::suggestMapping($supplier, $import, $columns, $sampleRows);

        $normalized = [];
        foreach ($columns as $column) {
            $normalized[$this->normalizeHeaderForMatching($column)] = $column;
        }

        foreach (['pvp + iva', 'pvp+iva', 'pvp con iva'] as $saleColumn) {
            if (isset($normalized[$saleColumn])) {
                $mapping['price_tax_incl'] = $normalized[$saleColumn];
                break;
            }
        }
        foreach (['pvp iva'] as $saleColumn) {
            if (isset($normalized[$saleColumn])) {
                $mapping['price_tax_incl'] = $normalized[$saleColumn];
                break;
            }
        }

        if (isset($normalized['neto'])) {
            $mapping['cost_price'] = $normalized['neto'];
        }

        return $mapping;
    }
}
