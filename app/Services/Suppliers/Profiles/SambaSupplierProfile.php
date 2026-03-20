<?php

namespace App\Services\Suppliers\Profiles;

class SambaSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'samba';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['cost_price'] = array_merge($base['cost_price'], [
            'precio de coste',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'precio pvp',
        ]);

        return $base;
    }
}
