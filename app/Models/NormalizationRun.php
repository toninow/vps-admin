<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NormalizationRun extends Model
{
    /**
     * Semántica de progreso:
     * - total_products: número total de normalized_products incluidos en el lote.
     * - processed_products: número de productos recorridos en la fase final del pipeline
     *   (Identity Engine / vinculación avanzada), útil para mostrar progreso real.
     */
    protected $fillable = [
        'import_id',
        'status',
        'total_products',
        'processed_products',
        'errors',
        'duration_seconds',
        'error_message',
        'started_at',
        'finished_at',
    ];

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $appends = [
        'percent_complete',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class, 'import_id');
    }

    public function getPercentCompleteAttribute(): float
    {
        if ($this->total_products > 0) {
            return round(($this->processed_products / $this->total_products) * 100, 2);
        }

        return 0.0;
    }
}

