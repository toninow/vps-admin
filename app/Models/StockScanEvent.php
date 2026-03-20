<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockScanEvent extends Model
{
    protected $fillable = [
        'master_product_id',
        'app_device_id',
        'user_id',
        'quantity_before',
        'quantity_after',
        'delta',
        'source',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'delta' => 'integer',
    ];

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function appDevice(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
