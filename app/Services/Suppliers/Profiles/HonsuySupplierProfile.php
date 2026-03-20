<?php

namespace App\Services\Suppliers\Profiles;

class HonsuySupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'honsuy';
    }

    public function getMaturityLevel(): string
    {
        return 'generic';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_merge(['nombre'], $base['name'], [
            'Name',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'neto',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'sku',
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

        foreach (['nombre', 'name'] as $nameColumn) {
            if (isset($normalized[$nameColumn])) {
                $mapping['name'] = $normalized[$nameColumn];
                break;
            }
        }

        return $mapping;
    }
}
