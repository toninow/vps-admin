<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierImport extends Model
{
    protected $fillable = [
        'supplier_id',
        'user_id',
        'filename_original',
        'file_path',
        'file_type',
        'catalog_year',
        'status',
        'pipeline_status',
        'pipeline_stage',
        'pipeline_total',
        'pipeline_processed',
        'pipeline_percent',
        'pipeline_message',
        'total_rows',
        'processed_rows',
        'error_rows',
        'mapping_snapshot',
        'started_at',
        'finished_at',
        'pipeline_started_at',
        'pipeline_finished_at',
        'error_message',
    ];

    protected $casts = [
        'mapping_snapshot' => 'array',
        'catalog_year' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'pipeline_started_at' => 'datetime',
        'pipeline_finished_at' => 'datetime',
    ];

    protected $appends = [
        'pipeline_is_running',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplierImportRows(): HasMany
    {
        return $this->hasMany(SupplierImportRow::class, 'supplier_import_id');
    }

    public function normalizedProducts(): HasMany
    {
        return $this->hasMany(NormalizedProduct::class, 'supplier_import_id');
    }

    public function normalizationRuns(): HasMany
    {
        return $this->hasMany(NormalizationRun::class, 'import_id');
    }

    public function getPipelineIsRunningAttribute(): bool
    {
        return in_array($this->pipeline_status, ['queued', 'processing'], true);
    }
}
