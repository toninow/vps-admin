<?php

namespace App\Services\Suppliers\Profiles;

class LudwigNlSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'ludwig_nl';
    }

    public function getMaturityLevel(): string
    {
        return 'generic';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_merge($base['name'], [
            'product',
        ]);
        $base['description'] = array_merge($base['description'], [
            'description',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'brand',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'item',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'category',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'standard trade',
        ]);

        return $base;
    }
}
