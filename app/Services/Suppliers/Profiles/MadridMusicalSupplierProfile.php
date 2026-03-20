<?php

namespace App\Services\Suppliers\Profiles;

class MadridMusicalSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'madridmusical';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = ['Nombre'];
        $base['summary'] = ['Descripcion', 'Nombre'];
        $base['description'] = ['Descripcion'];
        $base['supplier_reference'] = ['Referencia', 'Referencia del fabricante'];
        $base['brand'] = ['Marca'];
        $base['cost_price'] = ['Neto'];
        $base['price_tax_incl'] = ['PVPR'];
        $base['image_urls'] = ['Imagenes'];

        return $base;
    }

    public function suggestMapping(\App\Models\Supplier $supplier, \App\Models\SupplierImport $import, array $columns, array $sampleRows): array
    {
        $mapping = parent::suggestMapping($supplier, $import, $columns, $sampleRows);

        // El proveedor no expone un EAN real; "ID Producto" es un identificador interno.
        unset($mapping['ean13']);

        return $mapping;
    }
}
