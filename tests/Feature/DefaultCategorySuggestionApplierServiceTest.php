<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NormalizedProduct;
use App\Models\ProductCategorySuggestion;
use App\Models\Supplier;
use App\Models\SupplierImport;
use App\Services\Normalization\DefaultCategorySuggestionApplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultCategorySuggestionApplierServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_apply_when_top_score_is_tied_between_categories(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Proveedor test',
            'slug' => 'proveedor-test',
            'is_active' => true,
        ]);

        $import = SupplierImport::query()->create([
            'supplier_id' => $supplier->id,
            'filename_original' => 'test.csv',
            'file_path' => 'imports/test.csv',
            'file_type' => 'csv',
            'status' => 'processed',
        ]);

        $guitarras = Category::query()->create([
            'name' => 'Guitarras',
            'slug' => 'guitarras',
            'is_active' => true,
        ]);

        $ukeleles = Category::query()->create([
            'parent_id' => $guitarras->id,
            'name' => 'Ukeleles',
            'slug' => 'ukeleles',
            'is_active' => true,
        ]);

        $accesorios = Category::query()->create([
            'name' => 'Accesorios',
            'slug' => 'accesorios',
            'is_active' => true,
        ]);

        $fundas = Category::query()->create([
            'parent_id' => $accesorios->id,
            'name' => 'Fundas',
            'slug' => 'fundas',
            'is_active' => true,
        ]);

        $product = NormalizedProduct::query()->create([
            'supplier_import_id' => $import->id,
            'supplier_id' => $supplier->id,
            'name' => 'GEWA Ukelele Soprano Manoa',
            'category_status' => 'unassigned',
        ]);

        ProductCategorySuggestion::query()->create([
            'normalized_product_id' => $product->id,
            'category_id' => $ukeleles->id,
            'source' => 'auto',
            'score' => 20.0,
        ]);

        ProductCategorySuggestion::query()->create([
            'normalized_product_id' => $product->id,
            'category_id' => $fundas->id,
            'source' => 'auto',
            'score' => 20.0,
        ]);

        $result = app(DefaultCategorySuggestionApplierService::class)->applyForProducts([$product->id], 15.0);

        $this->assertSame(0, $result['applied']);
        $this->assertNull($product->fresh()->category_id);
        $this->assertSame('unassigned', $product->fresh()->category_status);
    }
}
