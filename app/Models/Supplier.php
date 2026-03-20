<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function supplierImports(): HasMany
    {
        return $this->hasMany(SupplierImport::class);
    }

    public function supplierFieldMappings(): HasMany
    {
        return $this->hasMany(SupplierFieldMapping::class);
    }

    public function normalizedProducts(): HasMany
    {
        return $this->hasMany(NormalizedProduct::class);
    }
}
