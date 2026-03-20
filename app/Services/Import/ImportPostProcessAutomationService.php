<?php

namespace App\Services\Import;

use App\Models\MasterProduct;
use App\Models\ProductCategorySuggestion;
use App\Models\ProductEanIssue;
use App\Models\SupplierImport;
use Illuminate\Support\Facades\DB;

class ImportPostProcessAutomationService
{
    /**
     * Sincroniza tablas auxiliares con los maestros ya creados y aprueba
     * automáticamente los maestros seguros del lote.
     *
     * @return array{
     *   normalized_products:int,
     *   masters_in_import:int,
     *   synced_ean_issues:int,
     *   synced_category_suggestions:int,
     *   approval_candidates:int,
     *   approved_masters:int
     * }
     */
    public function finalizeImport(SupplierImport $import, ?callable $progressCallback = null): array
    {
        $normalizedIds = $import->normalizedProducts()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $totalProducts = count($normalizedIds);

        if ($normalizedIds === []) {
            return [
                'normalized_products' => 0,
                'masters_in_import' => 0,
                'synced_ean_issues' => 0,
                'synced_category_suggestions' => 0,
                'approval_candidates' => 0,
                'approved_masters' => 0,
            ];
        }

        $masterIds = $import->normalizedProducts()
            ->whereNotNull('master_product_id')
            ->pluck('master_product_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        $steps = 3;

        $this->reportProgress(
            1,
            $steps,
            $totalProducts,
            'Sincronizando incidencias EAN y sugerencias con el catálogo maestro...',
            $progressCallback
        );

        $syncedIssues = ProductEanIssue::query()
            ->whereIn('normalized_product_id', $normalizedIds)
            ->whereHas('normalizedProduct', function ($query) {
                $query->whereNotNull('master_product_id');
            })
            ->update([
                'master_product_id' => DB::raw('(SELECT master_product_id FROM normalized_products WHERE normalized_products.id = product_ean_issues.normalized_product_id)'),
            ]);

        $syncedSuggestions = ProductCategorySuggestion::query()
            ->whereIn('normalized_product_id', $normalizedIds)
            ->whereHas('normalizedProduct', function ($query) {
                $query->whereNotNull('master_product_id');
            })
            ->update([
                'master_product_id' => DB::raw('(SELECT master_product_id FROM normalized_products WHERE normalized_products.id = product_category_suggestions.normalized_product_id)'),
            ]);

        $this->reportProgress(
            2,
            $steps,
            $totalProducts,
            'Evaluando qué maestros del lote ya están listos para exportación automática...',
            $progressCallback
        );

        $approvalQuery = MasterProduct::query()
            ->whereIn('id', $masterIds)
            ->where('is_approved', false)
            ->whereNotNull('category_id')
            ->whereIn('category_status', ['suggested', 'confirmed'])
            ->whereNotNull('price_tax_incl')
            ->whereNotNull('cost_price')
            ->whereColumn('price_tax_incl', '>=', 'cost_price')
            ->whereDoesntHave('normalizedProducts.productEanIssues', function ($query) {
                $query->whereNull('resolved_at');
            });

        $approvalCandidates = (clone $approvalQuery)->count();
        $approvedIds = (clone $approvalQuery)->pluck('id');

        if ($approvedIds->isNotEmpty()) {
            MasterProduct::query()
                ->whereIn('id', $approvedIds)
                ->update([
                    'is_approved' => true,
                    'approved_at' => now(),
                    'approved_by_id' => null,
                ]);
        }

        $approvedCount = $approvedIds->count();

        $this->reportProgress(
            3,
            $steps,
            $totalProducts,
            sprintf(
                'Cierre automático completado: %d maestros consolidados y %d aprobados para exportación segura.',
                count($masterIds),
                $approvedCount
            ),
            $progressCallback
        );

        return [
            'normalized_products' => $totalProducts,
            'masters_in_import' => count($masterIds),
            'synced_ean_issues' => (int) $syncedIssues,
            'synced_category_suggestions' => (int) $syncedSuggestions,
            'approval_candidates' => (int) $approvalCandidates,
            'approved_masters' => $approvedCount,
        ];
    }

    protected function reportProgress(
        int $step,
        int $steps,
        int $totalProducts,
        string $message,
        ?callable $progressCallback = null
    ): void {
        if ($progressCallback === null) {
            return;
        }

        $basePercent = 90.0;
        $spanPercent = 10.0;
        $percent = round($basePercent + (($step / max(1, $steps)) * $spanPercent), 2);

        $progressCallback([
            'stage' => 'finalizing',
            'processed' => $totalProducts,
            'total' => $totalProducts,
            'percent' => min(100.0, $percent),
            'message' => $message,
        ]);
    }
}
