<?php

namespace App\Services\Export;

use App\Models\MasterProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MasterApprovalService
{
    public function __construct(
        protected PrestashopProductCsvService $prestashopProductCsvService,
    ) {}

    public function collectApprovableIds(
        ?array $masterIds = null,
        bool $allowSuggestedCategory = true,
        ?int $supplierId = null,
        int $limit = 0,
    ): Collection {
        return $this->filterMasterIds(
            $this->baseApprovalQuery($masterIds, $allowSuggestedCategory, $supplierId)
                ->where('is_approved', false),
            $limit,
            true
        );
    }

    public function collectRevokableApprovedIds(
        ?array $masterIds = null,
        ?int $supplierId = null,
        int $limit = 0
    ): Collection
    {
        return $this->filterMasterIds(
            $this->baseRevocationQuery($masterIds, $supplierId),
            $limit,
            false
        );
    }

    public function approve(Collection|array $ids, ?int $approvedById = null): int
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return MasterProduct::query()
            ->whereIn('id', $ids)
            ->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by_id' => $approvedById,
            ]);
    }

    public function revoke(Collection|array $ids): int
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return MasterProduct::query()
            ->whereIn('id', $ids)
            ->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by_id' => null,
            ]);
    }

    protected function baseApprovalQuery(
        ?array $masterIds,
        bool $allowSuggestedCategory,
        ?int $supplierId,
    ): Builder {
        return MasterProduct::query()
            ->with([
                'masterProductSuppliers.normalizedProduct.supplier',
                'masterProductSuppliers.normalizedProduct.supplierImport',
                'normalizedProducts.supplier',
                'normalizedProducts.supplierImport',
                'normalizedProducts.productEanIssues',
            ])
            ->when(
                $masterIds !== null && $masterIds !== [],
                fn (Builder $builder) => $builder->whereIn('id', $masterIds)
            )
            ->whereNotNull('category_id')
            ->when(! $allowSuggestedCategory, fn (Builder $builder) => $builder->where('category_status', 'confirmed'))
            ->whereDoesntHave('normalizedProducts.productEanIssues', fn (Builder $builder) => $builder->whereNull('resolved_at'))
            ->when(
                $supplierId !== null,
                fn (Builder $builder) => $builder->whereHas('normalizedProducts', fn (Builder $inner) => $inner->where('supplier_id', $supplierId))
            )
            ->orderBy('id');
    }

    protected function baseRevocationQuery(?array $masterIds, ?int $supplierId): Builder
    {
        return MasterProduct::query()
            ->with([
                'masterProductSuppliers.normalizedProduct.supplier',
                'masterProductSuppliers.normalizedProduct.supplierImport',
                'normalizedProducts.supplier',
                'normalizedProducts.supplierImport',
                'normalizedProducts.productEanIssues',
            ])
            ->when(
                $masterIds !== null && $masterIds !== [],
                fn (Builder $builder) => $builder->whereIn('id', $masterIds)
            )
            ->where('is_approved', true)
            ->when(
                $supplierId !== null,
                fn (Builder $builder) => $builder->whereHas('normalizedProducts', fn (Builder $inner) => $inner->where('supplier_id', $supplierId))
            )
            ->orderBy('id');
    }

    protected function filterMasterIds(Builder $query, int $limit, bool $expectExportable): Collection
    {
        $ids = collect();
        $remaining = $limit > 0 ? $limit : null;

        $query->chunkById(250, function ($products) use (&$ids, &$remaining, $expectExportable) {
            foreach ($products as $product) {
                $payload = $this->prestashopProductCsvService->buildForMasterProduct($product);
                $isExportable = (bool) data_get($payload, 'metrics.is_exportable', false);

                if ($isExportable === $expectExportable) {
                    $ids->push((int) $product->id);

                    if ($remaining !== null) {
                        $remaining--;
                        if ($remaining <= 0) {
                            return false;
                        }
                    }
                }
            }

            return $remaining === null || $remaining > 0;
        });

        return $ids;
    }
}
