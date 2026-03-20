<?php

namespace App\Services\Suppliers\Profiles;

class DaddarioSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'daddario';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'upc',
        ]);
        $base['name'] = array_merge($base['name'], [
            'nombre del producto', 'nombre del producto web',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'descripcion del producto', 'sub brand descripcion',
        ]);
        $base['description'] = array_merge($base['description'], [
            'descripcion del producto', 'sub brand descripcion', 'bullet 1', 'bullet 2', 'bullet 3', 'bullet 4', 'bullet 5',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'brand', 'manufacturer',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'pre pay price euro',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'rrp euros', 'pre pay price euro',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'type of product', 'category',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'numero articulo',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'image main url', 'image oninstrumentimage1 url',
        ]);
        $base['tags'] = array_merge($base['tags'], [
            'search keywords',
        ]);

        return $base;
    }
}
