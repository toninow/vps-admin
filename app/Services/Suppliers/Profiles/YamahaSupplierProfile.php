<?php

namespace App\Services\Suppliers\Profiles;

class YamahaSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'yamaha';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'ean/gtin', 'ean gtin', 'gtin', 'ean', 'barcode',
        ]);
        $base['name'] = array_merge($base['name'], [
            'designación de artículo', 'designacion de articulo', 'designación de articulo',
            'product description', 'item description', 'articulo',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'descipción de producto', 'descripcion de producto',
        ]);
        $base['description'] = array_merge($base['description'], [
            'descipción de producto', 'descripcion de producto',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'número de artículo', 'numero de articulo', 'yamaha code', 'yamaha part no', 'artikelnummer',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'stock', 'cantidad', 'menge',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'precio de coste es sin iva eur', 'precio de coste sin iva', 'precio coste es sin iva',
            'cost price', 'einkaufspreis',
            // En algunos catálogos YAMAHA el \"Trade Price\" se usa como coste (excl. VAT)
            'trade price', 'trade price es excl vat eur',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvp es incl. 21% iva eur', 'pvp es incl 21 iva eur', 'pvp incl iva',
            'verkaufspreis', 'pvp', 'price',
            // SRP suele ser el PVP recomendado (incl. VAT)
            'srp', 'srp es incl. 21% vat eur', 'srp es incl 21 vat eur',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'grupo de producto', 'catagoría de producto', 'categoria de producto',
            'grupo producto', 'categoria producto',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marca', 'brand', 'yamaha',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'imagen', 'image', 'url imagen', 'product image',
        ]);

        return $base;
    }
}
