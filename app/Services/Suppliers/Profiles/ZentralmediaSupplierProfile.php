<?php

namespace App\Services\Suppliers\Profiles;

class ZentralmediaSupplierProfile extends GermanCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'zentralmedia';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_merge($base['name'], [
            'descripcion', 'descripcion articulo', 'descripcion2 articulo',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'descripcion', 'descripcion articulo',
        ]);
        $base['description'] = array_merge($base['description'], [
            'descripcion', 'descripcion articulo', 'descripcion2 articulo', 'descripcion linea',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marcaproducto', 'marca producto',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'codigoarticulo', 'codigo articulo',
        ]);
        $base['cost_price'] = array_merge([
            'precio compra', 'preciocompra',
        ], $base['cost_price'], [
            'precio venta', 'precioventa',
        ]);
        $base['price_tax_incl'] = array_merge([
            'pvp con iva', 'pvp coniva', 'pvp_coniva',
        ], $base['price_tax_incl'], [
            'precio venta', 'precioventa', 'precio recomendado',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'codigofamilia', 'codigo familia', 'descripcionlinea', 'descripcion linea',
        ]);

        return $base;
    }

    public function suggestMapping(\App\Models\Supplier $supplier, \App\Models\SupplierImport $import, array $columns, array $sampleRows): array
    {
        $mapping = parent::suggestMapping($supplier, $import, $columns, $sampleRows);

        $normalized = [];
        foreach ($columns as $column) {
            $normalized[$this->normalizeHeaderForMatching($column)] = $column;
        }

        foreach (['pvp con iva', 'pvp coniva', 'pvp_coniva', 'precio venta', 'precioventa'] as $saleColumn) {
            if (isset($normalized[$saleColumn])) {
                $mapping['price_tax_incl'] = $normalized[$saleColumn];
                break;
            }
        }

        foreach (['precio compra', 'preciocompra', 'precio venta', 'precioventa'] as $costColumn) {
            if (isset($normalized[$costColumn])) {
                $mapping['cost_price'] = $normalized[$costColumn];
                break;
            }
        }

        foreach (['codigoarticulo', 'codigo articulo'] as $referenceColumn) {
            if (isset($normalized[$referenceColumn])) {
                $mapping['supplier_reference'] = $normalized[$referenceColumn];
                break;
            }
        }

        return $mapping;
    }
}
