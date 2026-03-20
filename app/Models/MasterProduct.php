<?php

namespace App\Models;

use App\Support\CategoryPathFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProduct extends Model
{
    protected $fillable = [
        'ean13',
        'seed_normalized_product_id',
        'reference',
        'name',
        'summary',
        'description',
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
        'search_keywords_normalized',
        'is_approved',
        'approved_at',
        'approved_by_id',
        'version',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_tax_incl' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'active' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'version' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function masterProductSuppliers(): HasMany
    {
        return $this->hasMany(MasterProductSupplier::class, 'master_product_id');
    }

    public function normalizedProducts(): HasMany
    {
        return $this->hasMany(NormalizedProduct::class, 'master_product_id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'master_product_id');
    }

    public function stockChanges(): HasMany
    {
        return $this->hasMany(StockChange::class, 'master_product_id');
    }

    public function stockScanEvents(): HasMany
    {
        return $this->hasMany(StockScanEvent::class, 'master_product_id');
    }

    public function formattedCategoryPath(): string
    {
        return CategoryPathFormatter::formatForDisplay($this->category_path_export);
    }
}
