<?php

namespace App\Services\Suppliers\Profiles;

class EnriqueKellerSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'cod. producto',
            'cod producto',
            'codigo producto',
            'código producto',
        ]);

        $base['cost_price'] = array_merge($base['cost_price'], [
            'neto',
        ]);

        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvpr',
        ]);

        return $base;
    }

    public function getLogicalCode(): string
    {
        return 'enrique_keller';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }
}
