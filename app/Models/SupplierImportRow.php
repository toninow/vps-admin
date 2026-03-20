<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierImportRow extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_ERROR = 'error';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'supplier_import_id',
        'row_index',
        'raw_data',
        'normalized_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'normalized_data' => 'array',
    ];

    public function supplierImport(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class);
    }
}
