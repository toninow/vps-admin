<?php

namespace App\Services\Normalization;

use App\Models\NormalizationRun;
use App\Models\SupplierImport;
use App\Models\NormalizedProduct;
use App\Services\Identity\ProductIdentityEngine;

class NormalizationPipelineService
{
    public function __construct(
        protected AdvancedNormalizationService $advancedNormalization,
        protected EanIssueService $eanIssueService,
        protected CategorySuggestionService $categorySuggestion,
        protected DefaultCategorySuggestionApplierService $defaultCategorySuggestionApplier,
        protected ImageUrlCleanerService $imageUrlCleaner,
        protected DuplicateDetectionService $duplicateDetection,
        protected MasterProductLinkingService $masterLinking,
        protected ProductIdentityEngine $identityEngine,
    ) {}

    /**
     * Ejecuta el pipeline sobre los normalized_products de una importación
     * y registra la ejecución en normalization_runs.
     */
    public function runForImport(SupplierImport $import, ?callable $progressCallback = null): array
    {
        $ids = $import->normalizedProducts()->pluck('id')->all();
        if (empty($ids)) {
            return ['message' => 'No hay productos normalizados en esta importación.', 'steps' => []];
        }

        $startedAt = now();
        $run = NormalizationRun::create([
            'import_id' => $import->id,
            'status' => NormalizationRun::STATUS_RUNNING,
            'total_products' => count($ids),
            'processed_products' => 0,
            'errors' => 0,
            'duration_seconds' => null,
            'error_message' => null,
            'started_at' => $startedAt,
            'finished_at' => null,
        ]);

        try {
            $steps = [];
            $totalStages = 9;

            $r1 = $this->advancedNormalization->normalize($ids);
            $steps['advanced_normalization'] = $r1;
            $this->reportStageProgress($run, 1, $totalStages, 'normalizing', 'Generando etiquetas y limpiando textos comerciales...', $progressCallback);

            $r2 = $this->eanIssueService->detectAndRecordIssues($ids);
            $steps['ean_issues'] = $r2;
            $this->reportStageProgress($run, 2, $totalStages, 'normalizing', 'Revisando EAN y detectando incidencias...', $progressCallback);

            $r3 = $this->categorySuggestion->suggestForProducts($ids);
            $steps['category_suggestions'] = $r3;
            $this->reportStageProgress($run, 3, $totalStages, 'normalizing', 'Calculando rutas y categorías sugeridas...', $progressCallback);

            $r4 = $this->defaultCategorySuggestionApplier->applyForProducts($ids);
            $steps['category_defaults'] = $r4;
            $this->reportStageProgress($run, 4, $totalStages, 'normalizing', 'Guardando la mejor ruta sugerida en cada producto...', $progressCallback);

            // Recalcula tags y demás campos derivados con la ruta sugerida ya aplicada.
            $r5 = $this->advancedNormalization->normalize($ids);
            $steps['advanced_normalization_after_categories'] = $r5;
            $this->reportStageProgress($run, 5, $totalStages, 'normalizing', 'Refrescando etiquetas con la ruta ya sugerida...', $progressCallback);

            $r6 = $this->imageUrlCleaner->cleanAndSaveForProducts($ids);
            $steps['image_urls'] = $r6;
            $this->reportStageProgress($run, 6, $totalStages, 'normalizing', 'Limpiando y consolidando URLs de imágenes...', $progressCallback);

            $r7 = $this->duplicateDetection->detectForProducts($ids);
            $steps['duplicates'] = $r7;
            $this->reportStageProgress($run, 7, $totalStages, 'normalizing', 'Buscando duplicados en el lote...', $progressCallback);

            // Product Identity Engine: resolver master_product por múltiples señales
            $identityLinked = 0;
            $products = NormalizedProduct::whereIn('id', $ids)->get();
            foreach ($products as $product) {
                if ($product->master_product_id === null) {
                    $this->identityEngine->resolveMasterProduct($product);
                    $identityLinked++;
                }
            }
            $steps['identity_engine'] = [
                'processed' => $products->count(),
                'attempted' => $identityLinked,
            ];
            $this->reportStageProgress($run, 8, $totalStages, 'normalizing', 'Intentando enlazar productos con el catálogo maestro...', $progressCallback);

            $r8 = $this->masterLinking->linkOrCreateForProducts($ids);
            $steps['master_linking'] = $r8;
            $this->reportStageProgress($run, 9, $totalStages, 'normalizing', 'Cerrando la consolidación del lote...', $progressCallback);

            $finishedAt = now();
            $run->update([
                'status' => NormalizationRun::STATUS_COMPLETED,
                'processed_products' => $run->total_products,
                'errors' => $this->countErrorsFromSteps($steps),
                'duration_seconds' => $finishedAt->diffInSeconds($startedAt),
                'error_message' => null,
                'finished_at' => $finishedAt,
            ]);

            $percent = 0.0;
            if ($run->total_products > 0) {
                $percent = round(($run->processed_products / $run->total_products) * 100, 2);
            }

            return [
                'message' => 'Pipeline de normalización completado.',
                'steps' => $steps,
                'product_ids' => $ids,
                'total_products' => $run->total_products,
                'processed_products' => $run->processed_products,
                'percent_complete' => $percent,
            ];
        } catch (\Throwable $e) {
            $run->update([
                'status' => NormalizationRun::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Ejecuta el pipeline sobre una lista de IDs de normalized_products.
     */
    public function runForNormalizedProductIds(array $normalizedProductIds): array
    {
        if (empty($normalizedProductIds)) {
            return ['message' => 'No hay productos indicados.', 'steps' => []];
        }

        $steps = [];
        $steps['advanced_normalization'] = $this->advancedNormalization->normalize($normalizedProductIds);
        $steps['ean_issues'] = $this->eanIssueService->detectAndRecordIssues($normalizedProductIds);
        $steps['category_suggestions'] = $this->categorySuggestion->suggestForProducts($normalizedProductIds);
        $steps['category_defaults'] = $this->defaultCategorySuggestionApplier->applyForProducts($normalizedProductIds);
        $steps['advanced_normalization_after_categories'] = $this->advancedNormalization->normalize($normalizedProductIds);
        $steps['image_urls'] = $this->imageUrlCleaner->cleanAndSaveForProducts($normalizedProductIds);
        $steps['duplicates'] = $this->duplicateDetection->detectForProducts($normalizedProductIds);

        $identityLinked = 0;
        $products = NormalizedProduct::whereIn('id', $normalizedProductIds)->get();
        foreach ($products as $product) {
            if ($product->master_product_id === null) {
                $this->identityEngine->resolveMasterProduct($product);
                $identityLinked++;
            }
        }
        $steps['identity_engine'] = [
            'processed' => $products->count(),
            'attempted' => $identityLinked,
        ];

        $steps['master_linking'] = $this->masterLinking->linkOrCreateForProducts($normalizedProductIds);

        return [
            'message' => 'Pipeline de normalización completado.',
            'steps' => $steps,
            'product_ids' => $normalizedProductIds,
        ];
    }

    protected function reportStageProgress(
        NormalizationRun $run,
        int $completedStages,
        int $totalStages,
        string $stage,
        string $message,
        ?callable $progressCallback = null
    ): void {
        $processed = $totalStages > 0
            ? min($run->total_products, (int) round(($run->total_products * $completedStages) / $totalStages))
            : 0;

        $run->forceFill([
            'processed_products' => $processed,
        ])->save();

        if ($progressCallback !== null) {
            $progressCallback([
                'stage' => $stage,
                'processed' => $processed,
                'total' => $run->total_products,
                'percent' => $run->percent_complete,
                'message' => $message,
            ]);
        }
    }

    /**
     * Intenta derivar un número de errores a partir de los resultados de los pasos.
     */
    protected function countErrorsFromSteps(array $steps): int
    {
        $errorKeys = ['errors', 'error_count', 'issues_created'];
        $total = 0;

        foreach ($steps as $step) {
            if (!is_array($step)) {
                continue;
            }
            foreach ($errorKeys as $key) {
                if (isset($step[$key]) && is_numeric($step[$key])) {
                    $total += (int) $step[$key];
                }
            }
        }

        return $total;
    }
}
