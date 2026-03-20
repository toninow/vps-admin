<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterProductSupplier extends Model
{
    protected $fillable = [
        'master_product_id',
        'normalized_product_id',
        'supplier_id',
        'supplier_reference',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
