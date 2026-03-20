<?php

namespace App\Services\Suppliers\Profiles;

class EuromusicaSupplierProfile extends SpanishCatalogSupplierProfile
{
    public function getLogicalCode(): string
    {
        return 'euromusica';
    }

    public function getMaturityLevel(): string
    {
        return 'semi_specific';
    }
}
