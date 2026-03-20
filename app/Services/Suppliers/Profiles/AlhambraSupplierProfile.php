<?php

namespace App\Services\Suppliers\Profiles;

class AlhambraSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'alhambra';
    }

    public function getMaturityLevel(): string
    {
        return 'generic';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'precio web',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'codigo',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'fabricado por',
        ]);

        return $base;
    }
}
