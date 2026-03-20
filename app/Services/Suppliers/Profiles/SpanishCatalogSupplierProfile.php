<?php

namespace App\Services\Suppliers\Profiles;

/**
 * Perfil base para catálogos en español. No registrar en el resolver; extender por Tico, Vallestrade, etc.
 */
abstract class SpanishCatalogSupplierProfile extends GenericSupplierProfile
{
    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'codigo barras', 'cod barras',
        ]);
        $base['name'] = array_merge($base['name'], [
            'nombre producto', 'descripcion', 'producto', 'articulo', 'titulo', 'denominacion',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'resumen', 'descripcion',
        ]);
        $base['description'] = array_merge($base['description'], [
            'descripcion ampliada', 'detalle', 'observaciones', 'ficha', 'ficha tecnica',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'codigo', 'codigo producto', 'codigo articulo', 'ref proveedor', 'modelo',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'existencias', 'unidades', 'disponible',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'precio coste', 'precio compra', 'pc', 'precio de coste', 'precio distribuidor',
            'neto', 'pre neto', 'precioneto',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'precio publico', 'pv', 'precio venta', 'precio recomendado', 'pvpr',
            'pvp con iva', 'pvp coniva', 'pvp_coniva', 'precio web',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'familia', 'departamento', 'grupo', 'tipo',
        ]);
        $base['tags'] = array_merge($base['tags'], [
            'etiquetas', 'palabras clave',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'imagen', 'url imagen', 'foto', 'imagen1', 'imagen2', 'url_imagen',
        ]);

        return $base;
    }
}
