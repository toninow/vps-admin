<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'platform',
        'last_sync_at',
        'is_active',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockScanEvents(): HasMany
    {
        return $this->hasMany(StockScanEvent::class);
    }

    public function mobileSyncQueueItems(): HasMany
    {
        return $this->hasMany(MobileSyncQueue::class, 'app_device_id');
    }
}
