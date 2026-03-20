<?php

namespace App\Services\Suppliers\Profiles;

/**
 * Perfil genérico. Claves de getHeaderAliases = ImportTransformerService::TARGET_FIELDS (única convención).
 */
class GenericSupplierProfile extends BaseSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'generic';
    }

    public function getMaturityLevel(): string
    {
        return 'generic';
    }

    /**
     * Alias por target field. Solo claves usadas por el transformer.
     *
     * @return array<string, string[]>
     */
    public function getHeaderAliases(): array
    {
        return [
            'name' => ['nombre', 'name', 'descripcion corta', 'titulo', 'title', 'product name', 'designation'],
            'summary' => ['descripcion corta', 'short description', 'resumen', 'summary'],
            'description' => ['descripcion', 'description', 'detalle', 'descripcion larga', 'long description'],
            'ean13' => ['ean', 'codigo ean', 'ean13', 'cod barras', 'barcode', 'gtin', 'upc'],
            'quantity' => ['stock', 'cantidad', 'qty', 'existencias', 'quantity'],
            'price_tax_incl' => [
                'pvp', 'pvpr', 'precio venta', 'precio recomendado', 'precio venta recomendado',
                'precio', 'price', 'sale price', 'pvp con iva', 'pvp coniva', 'pvp_coniva', 'precio web',
            ],
            'cost_price' => [
                'coste', 'costo', 'precio compra', 'precio_compra', 'cost price', 'precio coste',
                'precio de coste', 'precio distribuidor', 'precio neto', 'neto', 'pre neto',
                'precioneto', 'retail', 'retailer', 'standard trade',
            ],
            'brand' => ['marca', 'brand', 'fabricante'],
            'category_path_export' => ['categoria', 'category', 'familia', 'seccion', 'category path'],
            'tags' => ['tags', 'etiquetas', 'keywords'],
            'supplier_reference' => ['ref proveedor', 'ref_proveedor', 'supplier ref', 'cod proveedor', 'ref', 'referencia', 'modelo', 'sku', 'cod articulo', 'codigo articulo'],
            'image_urls' => ['imagen', 'image', 'foto', 'url img', 'url_imagen', 'picture', 'imagen1', 'imagen2', 'image1', 'image2', 'photo', 'img', 'url imagen'],
        ];
    }
}
