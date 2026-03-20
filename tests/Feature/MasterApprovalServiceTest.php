<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MasterProduct;
use App\Services\Export\MasterApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_collects_only_masters_that_are_really_exportable(): void
    {
        $category = Category::query()->create([
            'name' => 'Guitarras',
            'slug' => 'guitarras',
            'is_active' => true,
        ]);

        $exportable = MasterProduct::query()->create([
            'name' => 'Producto exportable',
            'category_id' => $category->id,
            'category_status' => 'suggested',
            'price_tax_incl' => 121,
            'cost_price' => 50,
        ]);

        MasterProduct::query()->create([
            'name' => 'Producto roto',
            'category_id' => $category->id,
            'category_status' => 'suggested',
            'price_tax_incl' => 10,
            'cost_price' => 50,
        ]);

        $ids = app(MasterApprovalService::class)->collectApprovableIds();

        $this->assertSame([$exportable->id], $ids->all());
    }

    public function test_it_collects_approved_masters_that_no_longer_match_real_exportability(): void
    {
        $category = Category::query()->create([
            'name' => 'Bateria',
            'slug' => 'bateria',
            'is_active' => true,
        ]);

        MasterProduct::query()->create([
            'name' => 'Aprobado sano',
            'category_id' => $category->id,
            'category_status' => 'suggested',
            'price_tax_incl' => 121,
            'cost_price' => 50,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $approvedBroken = MasterProduct::query()->create([
            'name' => 'Aprobado roto',
            'category_id' => $category->id,
            'category_status' => 'suggested',
            'price_tax_incl' => 10,
            'cost_price' => 50,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $ids = app(MasterApprovalService::class)->collectRevokableApprovedIds();

        $this->assertSame([$approvedBroken->id], $ids->all());
    }
}
