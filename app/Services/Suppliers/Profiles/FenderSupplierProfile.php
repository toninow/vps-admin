<?php

namespace App\Services\Suppliers\Profiles;

/**
 * Fender: name = nombre del producto; summary = nombre del modelo (corto); description sin duplicar nombre.
 */
class FenderSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'fender';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'n.º upc', 'nº upc', 'numero upc', 'upc', 'barcode upc', 'upc12',
        ]);
        $base['name'] = array_merge($base['name'], [
            'nombre del producto', 'product name', 'item name', 'nombre producto',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'nombre del modelo', 'nombre modelo',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marca', 'fender',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'número de modelo', 'numero de modelo', 'número de modelo formateado',
            'numero de modelo formateado', 'fender sku', 'fender id', 'model number', 'sku',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'n.º de inventario', 'nº de inventario', 'numero de inventario', 'inventario',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'precio', 'price', 'precio venta',
        ]);
        // Coste: priorizamos el campo real de distribuidor para evitar que caiga en la misma
        // columna que `price_tax_incl` (por ejemplo, `Precio`).
        $base['cost_price'] = array_merge([], [
            'precio neto del distribuidor',
            'net price',
            'cost price',
            'einkaufspreis',
        ]);

        // Categoría: evitar `Jerarquía de productos` si se comporta como código numérico.
        $base['category_path_export'] = array_merge([], [
            'tipo de producto',
            'subtipo de producto',
            'estilo',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'image', 'product image', 'imagen', 'image url', 'url imagen', 'url de imagen',
        ]);

        return $base;
    }
}
