<?php

namespace App\Services\Suppliers\Profiles;

class EarproSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'earpro';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_merge($base['name'], ['descripcion', 'modelo']);
        $base['summary'] = array_merge($base['summary'], ['descripcion']);
        $base['description'] = array_merge($base['description'], ['descripcion_larga']);
        $base['cost_price'] = array_merge($base['cost_price'], ['neto pro partner sin iva']);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], ['street price iva incl']);
        $base['category_path_export'] = array_merge($base['category_path_export'], ['categoria']);
        $base['image_urls'] = array_merge($base['image_urls'], ['url']);

        return $base;
    }
}
