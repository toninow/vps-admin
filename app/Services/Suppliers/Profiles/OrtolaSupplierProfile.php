<?php

namespace App\Services\Suppliers\Profiles;

class OrtolaSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'ortola';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'ean',
        ]);
        $base['name'] = array_merge($base['name'], [
            'nombre', 'castellano', 'descripcion', 'nombre_2',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'castellano', 'descripcion', 'nombre_2', 'nombre_4',
        ]);
        $base['description'] = array_merge($base['description'], [
            'det castellano', 'det ingles', 'descripcion', 'castellano',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marca',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'cantidad',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'pre neto',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvp recomendado',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'referencia', 'codigoycolor', 'codigo',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'familia', 'subfamilia', 'nombre_2',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'url1', 'url2',
        ]);

        return $base;
    }
}
