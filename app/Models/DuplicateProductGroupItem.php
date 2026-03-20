<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateProductGroupItem extends Model
{
    protected $fillable = [
        'duplicate_product_group_id',
        'normalized_product_id',
        'master_product_id',
    ];

    public function duplicateProductGroup(): BelongsTo
    {
        return $this->belongsTo(DuplicateProductGroup::class);
    }

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
