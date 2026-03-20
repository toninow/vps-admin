<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategorySuggestion extends Model
{
    protected $fillable = [
        'normalized_product_id',
        'master_product_id',
        'category_id',
        'source',
        'score',
        'accepted_at',
    ];

    protected $casts = [
        'score' => 'decimal:4',
        'accepted_at' => 'datetime',
    ];

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
