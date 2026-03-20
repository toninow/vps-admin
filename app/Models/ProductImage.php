<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'master_product_id',
        'normalized_product_id',
        'url_original',
        'path_local',
        'position',
        'is_cover',
        'width',
        'height',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class);
    }
}
