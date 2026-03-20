<?php

namespace App\Console\Commands;

use App\Models\NormalizedProduct;
use App\Services\Normalization\CategorySuggestionService;
use App\Services\Normalization\DefaultCategorySuggestionApplierService;
use App\Services\Normalization\EanIssueService;
use Illuminate\Console\Command;

class BackfillCatalogReviewDataCommand extends Command
{
    protected $signature = 'catalog:backfill-review-data
                            {--chunk=500 : Tamano de lote}
                            {--supplier-id= : Limitar a un proveedor concreto}
                            {--missing-categories-only : Solo productos sin categoria sugerida aplicada}
                            {--skip-ean : No recalcular incidencias EAN}
                            {--skip-categories : No recalcular sugerencias de categoria}
                            {--apply-defaults : Aplicar la mejor sugerencia al producto y al maestro}';

    protected $description = 'Rellena incidencias EAN y sugerencias de categoria para normalized_products por lotes.';

    public function handle(
        EanIssueService $eanIssueService,
        CategorySuggestionService $categorySuggestionService,
        DefaultCategorySuggestionApplierService $defaultCategorySuggestionApplier,
    ): int {
        $chunk = max(1, (int) $this->option('chunk'));
        $skipEan = (bool) $this->option('skip-ean');
        $skipCategories = (bool) $this->option('skip-categories');
        $applyDefaults = (bool) $this->option('apply-defaults') && ! $skipCategories;

        $query = NormalizedProduct::query()
            ->orderBy('id')
            ->when(
                $this->option('supplier-id'),
                fn ($builder) => $builder->where('supplier_id', (int) $this->option('supplier-id'))
            )
            ->when(
                $this->option('missing-categories-only'),
                fn ($builder) => $builder->whereNull('category_id')
            );

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('No hay productos normalizados para procesar.');
            return self::SUCCESS;
        }

        $this->info("Backfill de revision sobre {$total} productos normalizados...");

        $stats = [
            'processed' => 0,
            'ean_issues_created' => 0,
            'ean_checked' => 0,
            'category_suggestions_created' => 0,
            'category_products_processed' => 0,
            'category_defaults_applied' => 0,
            'updated_masters' => 0,
        ];

        $query->select('id')->chunkById($chunk, function ($products) use (
            &$stats,
            $eanIssueService,
            $categorySuggestionService,
            $defaultCategorySuggestionApplier,
            $skipEan,
            $skipCategories,
            $applyDefaults,
            $total
        ) {
            $ids = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
            $batchCount = count($ids);

            if (! $skipEan) {
                $eanResult = $eanIssueService->detectAndRecordIssues($ids);
                $stats['ean_issues_created'] += (int) ($eanResult['issues_created'] ?? 0);
                $stats['ean_checked'] += (int) ($eanResult['total_checked'] ?? 0);
            }

            if (! $skipCategories) {
                $categoryResult = $categorySuggestionService->suggestForProducts($ids);
                $stats['category_suggestions_created'] += (int) ($categoryResult['suggestions_created'] ?? 0);
                $stats['category_products_processed'] += (int) ($categoryResult['total_products'] ?? 0);

                if ($applyDefaults) {
                    $defaultsResult = $defaultCategorySuggestionApplier->applyForProducts($ids);
                    $stats['category_defaults_applied'] += (int) ($defaultsResult['applied'] ?? 0);
                    $stats['updated_masters'] += (int) ($defaultsResult['updated_masters'] ?? 0);
                }
            }

            $stats['processed'] += $batchCount;

            $this->line(sprintf(
                'Lote: %d/%d | EAN +%d | sugerencias +%d | categorias aplicadas +%d',
                $stats['processed'],
                $total,
                $skipEan ? 0 : (int) ($eanResult['issues_created'] ?? 0),
                $skipCategories ? 0 : (int) ($categoryResult['suggestions_created'] ?? 0),
                $applyDefaults ? (int) ($defaultsResult['applied'] ?? 0) : 0,
            ));
        });

        $this->newLine();
        $this->info('Backfill completado.');
        $this->line('Productos procesados: ' . $stats['processed']);
        $this->line('Incidencias EAN creadas: ' . $stats['ean_issues_created']);
        $this->line('EAN revisados: ' . $stats['ean_checked']);
        $this->line('Sugerencias de categoria creadas: ' . $stats['category_suggestions_created']);
        $this->line('Productos evaluados para categoria: ' . $stats['category_products_processed']);
        $this->line('Categorias sugeridas aplicadas: ' . $stats['category_defaults_applied']);
        $this->line('Maestros actualizados por categoria: ' . $stats['updated_masters']);

        return self::SUCCESS;
    }
}
