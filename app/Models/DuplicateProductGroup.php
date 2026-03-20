<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuplicateProductGroup extends Model
{
    protected $fillable = [
        'ean13',
        'master_product_id',
        'status',
    ];

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function duplicateProductGroupItems(): HasMany
    {
        return $this->hasMany(DuplicateProductGroupItem::class, 'duplicate_product_group_id');
    }
}
