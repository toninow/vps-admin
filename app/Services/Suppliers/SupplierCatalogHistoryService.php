<?php

namespace App\Services\Suppliers;

use App\Models\NormalizedProduct;
use App\Models\Supplier;
use App\Models\SupplierImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierCatalogHistoryService
{
    /**
     * @return array{
     *   yearly_imports: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *   comparison: array<string, mixed>|null
     * }
     */
    public function buildForSupplier(Supplier $supplier): array
    {
        $yearRows = SupplierImport::query()
            ->selectRaw('catalog_year, MAX(id) as latest_import_id, COUNT(*) as imports_count, MAX(created_at) as imported_at')
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('catalog_year')
            ->groupBy('catalog_year')
            ->orderByDesc('catalog_year')
            ->get();

        $latestImports = SupplierImport::query()
            ->withCount('normalizedProducts')
            ->whereIn('id', $yearRows->pluck('latest_import_id')->filter())
            ->get()
            ->keyBy('id');

        $yearlyImports = $yearRows->map(function ($row) use ($latestImports) {
            $import = $latestImports->get($row->latest_import_id);

            return [
                'catalog_year' => (int) $row->catalog_year,
                'imports_count' => (int) $row->imports_count,
                'imported_at' => $row->imported_at,
                'latest_import' => $import,
                'normalized_products_count' => (int) ($import->normalized_products_count ?? 0),
            ];
        })->values();

        $comparison = null;
        if ($yearlyImports->count() >= 2) {
            $current = $yearlyImports->get(0)['latest_import'] ?? null;
            $previous = $yearlyImports->get(1)['latest_import'] ?? null;

            if ($current && $previous) {
                $comparison = $this->compareImports($current, $previous);
            }
        }

        return [
            'yearly_imports' => $yearlyImports,
            'comparison' => $comparison,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function compareImports(SupplierImport $currentImport, SupplierImport $previousImport): array
    {
        $currentBase = $this->referenceSnapshotSubquery($currentImport->id);
        $previousBase = $this->referenceSnapshotSubquery($previousImport->id);

        $shared = DB::query()
            ->fromSub($currentBase, 'current_catalog')
            ->joinSub($previousBase, 'previous_catalog', 'previous_catalog.supplier_reference', '=', 'current_catalog.supplier_reference')
            ->selectRaw('COUNT(*) as shared_references')
            ->selectRaw("SUM(CASE WHEN COALESCE(current_catalog.code_key, '') <> COALESCE(previous_catalog.code_key, '') THEN 1 ELSE 0 END) as changed_code_count")
            ->selectRaw('SUM(CASE WHEN current_catalog.price_tax_incl IS NOT NULL AND previous_catalog.price_tax_incl IS NOT NULL AND current_catalog.price_tax_incl <> previous_catalog.price_tax_incl THEN 1 ELSE 0 END) as changed_sale_price_count')
            ->selectRaw('SUM(CASE WHEN current_catalog.cost_price IS NOT NULL AND previous_catalog.cost_price IS NOT NULL AND current_catalog.cost_price <> previous_catalog.cost_price THEN 1 ELSE 0 END) as changed_cost_price_count')
            ->first();

        $addedCount = DB::query()
            ->fromSub($currentBase, 'current_catalog')
            ->leftJoinSub($previousBase, 'previous_catalog', 'previous_catalog.supplier_reference', '=', 'current_catalog.supplier_reference')
            ->whereNull('previous_catalog.supplier_reference')
            ->count();

        $removedCount = DB::query()
            ->fromSub($previousBase, 'previous_catalog')
            ->leftJoinSub($currentBase, 'current_catalog', 'current_catalog.supplier_reference', '=', 'previous_catalog.supplier_reference')
            ->whereNull('current_catalog.supplier_reference')
            ->count();

        return [
            'current_import' => $currentImport,
            'previous_import' => $previousImport,
            'shared_references' => (int) ($shared->shared_references ?? 0),
            'changed_code_count' => (int) ($shared->changed_code_count ?? 0),
            'changed_sale_price_count' => (int) ($shared->changed_sale_price_count ?? 0),
            'changed_cost_price_count' => (int) ($shared->changed_cost_price_count ?? 0),
            'added_references_count' => (int) $addedCount,
            'removed_references_count' => (int) $removedCount,
        ];
    }

    protected function referenceSnapshotSubquery(int $importId)
    {
        return NormalizedProduct::query()
            ->selectRaw('supplier_reference')
            ->selectRaw("MAX(CASE WHEN ean13 IS NOT NULL AND ean13 <> '' THEN ean13 WHEN barcode_raw IS NOT NULL AND barcode_raw <> '' THEN barcode_raw ELSE '' END) as code_key")
            ->selectRaw('MAX(price_tax_incl) as price_tax_incl')
            ->selectRaw('MAX(cost_price) as cost_price')
            ->where('supplier_import_id', $importId)
            ->whereNotNull('supplier_reference')
            ->where('supplier_reference', '<>', '')
            ->groupBy('supplier_reference');
    }
}
