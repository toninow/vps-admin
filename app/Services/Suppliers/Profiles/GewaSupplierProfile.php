<?php

namespace App\Services\Suppliers\Profiles;

/**
 * GEWA: ccgart/ccgartdot = referencia; nombre comercial y textos = ccgbel1/2/3 y specstext1/2.
 */
class GewaSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'gewa';
    }

    public function getMaturityLevel(): string
    {
        return 'specific';
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'ccgean', 'ean', 'gtin', 'barcode',
        ]);
        $base['name'] = array_merge($base['name'], [
            'ccgbel1', 'ccgbel2', 'ccgbel3', 'namk',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'specstext1', 'ccgbel2', 'text1', 'namk',
        ]);
        $base['description'] = array_merge($base['description'], [
            'specstext1', 'specstext2', 'text1', 'text2', 'ccgbel3',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marke',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'availablequantity', 'available quantity', 'menge',
        ]);
        // En los catálogos GEWA reales la columna `ccghev` trae el PVP y `ccgevk` el neto.
        $base['cost_price'] = array_merge($base['cost_price'], [
            'ccgevk', 'einkauf', 'einkaufspreis',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'ccghev', 'verkauf', 'verkaufspreis', 'uvp',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'ccgart', 'ccgartdot', 'artnr', 'artikelnummer', 'ref',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            // En los ficheros GEWA reales que estamos procesando, `ccgwkl/ccgbek` actúan
            // como códigos internos y pueden venir vacíos (o no ser texto visible).
            // Para `category_path_export` preferimos solo columnas que parezcan familia
            // o agrupación. `ccgbel1` suele comportarse como nombre comercial del producto.
            'ccgbel3',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'imagehq1', 'imagehq2', 'imagehq3', 'imagehq4', 'imagehq5', 'image hq', 'bild',
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

        foreach (['ccgbel1', 'ccgbel3', 'namk'] as $nameColumn) {
            if (isset($normalized[$nameColumn])) {
                $mapping['name'] = $normalized[$nameColumn];
                break;
            }
        }

        if (isset($normalized['ccghev'])) {
            $mapping['price_tax_incl'] = $normalized['ccghev'];
        }

        if (isset($normalized['ccgevk'])) {
            $mapping['cost_price'] = $normalized['ccgevk'];
        }

        return $mapping;
    }
}
