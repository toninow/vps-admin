<?php

namespace App\Models;

use App\Support\CategoryPathFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NormalizedProduct extends Model
{
    protected $fillable = [
        'supplier_import_id',
        'supplier_import_row_id',
        'master_product_id',
        'supplier_id',
        'supplier_reference',
        'name',
        'summary',
        'description',
        'ean13',
        'barcode_raw',
        'barcode_type',
        'barcode_status',
        'quantity',
        'price_tax_incl',
        'cost_price',
        'tax_rule_id',
        'warehouse',
        'active',
        'brand',
        'category_id',
        'category_status',
        'category_path_export',
        'tags',
        'image_urls',
        'validation_status',
        'ean_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_tax_incl' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'active' => 'integer',
        'image_urls' => 'array',
    ];

    public function supplierImport(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class);
    }

    public function supplierImportRow(): BelongsTo
    {
        return $this->belongsTo(SupplierImportRow::class);
    }

    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productEanIssues(): HasMany
    {
        return $this->hasMany(ProductEanIssue::class, 'normalized_product_id');
    }

    public function productCategorySuggestions(): HasMany
    {
        return $this->hasMany(ProductCategorySuggestion::class, 'normalized_product_id');
    }

    public function barcodeTypeLabel(): string
    {
        return match (strtolower((string) $this->barcode_type)) {
            'ean13' => 'EAN13',
            'upc12' => 'UPC12',
            'gtin8' => 'GTIN8',
            'sku' => 'SKU',
            'none' => 'Sin codigo',
            'unknown' => 'Codigo no reconocido',
            default => $this->barcode_type
                ? strtoupper(str_replace('_', ' ', (string) $this->barcode_type))
                : 'Codigo interno',
        };
    }

    public function barcodeStatusLabel(): string
    {
        return match (strtolower((string) $this->barcode_status)) {
            'ok' => $this->ean13 ? 'EAN13' : $this->barcodeTypeLabel(),
            'non_ean' => $this->barcodeTypeLabel(),
            'invalid_ean' => 'EAN invalido',
            'missing' => 'Sin codigo',
            default => $this->barcodeTypeLabel(),
        };
    }

    public function formattedCategoryPath(): string
    {
        return CategoryPathFormatter::formatForDisplay($this->category_path_export);
    }
}
