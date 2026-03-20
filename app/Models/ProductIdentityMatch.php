<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductIdentityMatch extends Model
{
    protected $fillable = [
        'normalized_product_id',
        'master_product_id',
        'match_type',
        'confidence_score',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
    ];

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class, 'normalized_product_id');
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }
}

