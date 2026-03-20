<?php

namespace App\Services\Suppliers\Profiles;

class AlgamSupplierProfile extends FrenchCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'algam';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['cost_price'] = array_merge($base['cost_price'], [
            'precio distribuidor',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvpii',
        ]);

        return $base;
    }
}
