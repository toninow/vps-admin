<?php

namespace App\Services\Suppliers\Profiles;

class RitmoSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'ritmo';
    }

    public function getMaturityLevel(): string
    {
        return 'generic';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['cost_price'] = array_merge($base['cost_price'], [
            'neto',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvpr', 'mapr',
        ]);

        return $base;
    }
}
