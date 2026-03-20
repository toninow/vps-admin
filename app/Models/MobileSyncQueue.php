<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSyncQueue extends Model
{
    protected $table = 'mobile_sync_queue';

    protected $fillable = [
        'app_device_id',
        'entity_type',
        'entity_id',
        'action',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function appDevice(): BelongsTo
    {
        return $this->belongsTo(AppDevice::class);
    }
}
