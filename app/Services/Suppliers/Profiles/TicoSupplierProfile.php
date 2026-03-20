<?php

namespace App\Services\Suppliers\Profiles;

class TicoSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'tico';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['cost_price'] = array_merge($base['cost_price'], [
            'precioneto',
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

        if (isset($normalized['pvp'])) {
            $mapping['price_tax_incl'] = $normalized['pvp'];
        }

        if (isset($normalized['precioneto'])) {
            $mapping['cost_price'] = $normalized['precioneto'];
        }

        return $mapping;
    }
}
