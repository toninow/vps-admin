<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductEanIssue extends Model
{
    protected $fillable = [
        'normalized_product_id',
        'master_product_id',
        'issue_type',
        'value_received',
        'resolved_at',
        'resolved_by_id',
        'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function normalizedProduct(): BelongsTo
    {
        return $this->belongsTo(NormalizedProduct::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
