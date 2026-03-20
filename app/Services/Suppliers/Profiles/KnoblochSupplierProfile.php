<?php

namespace App\Services\Suppliers\Profiles;

class KnoblochSupplierProfile extends GenericSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'knobloch';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }

    /**
     * Variantes de archivo: knobloch1, knobloch2, knobloch3...
     *
     * @return string[]
     */
    public function getFilePatterns(): array
    {
        return ['knobloch1', 'knobloch2', 'knobloch3'];
    }

    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['name'] = array_merge($base['name'], ['description']);
        $base['summary'] = array_merge($base['summary'], ['description']);
        $base['description'] = array_merge($base['description'], ['description']);
        $base['ean13'] = array_merge($base['ean13'], ['ean code', 'ean_code']);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], ['ref knobloch', 'knobloch ref']);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'pvp',
            'public price',
            'public recommended',
            'public price recommended',
            'public recommended price',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], ['retail', 'retailer']);
        $base['category_path_export'] = array_merge($base['category_path_export'], ['series', 'family']);

        return $base;
    }
}
