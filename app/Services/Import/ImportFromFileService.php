<?php

namespace App\Services\Import;

use App\Models\SupplierColumnAlias;
use App\Models\SupplierImport;
use App\Models\SupplierImportRow;
use App\Services\Import\FileReaderFactory;
use App\Services\Suppliers\SupplierProfileResolver;
use Illuminate\Support\Facades\DB;

/**
 * Crea importaciones desde un archivo en disco y aplica el flujo de mapeo/rows/transform.
 * Reutiliza la misma lógica que SupplierImportController.
 */
class ImportFromFileService
{
    public function __construct(
        protected FileTypeDetector $detector,
        protected FileReaderFactory $readerFactory,
        protected SupplierProfileResolver $profileResolver,
        protected ImportPipelineResetService $resetService
    ) {}

    /**
     * Construye el columns_map sugerido (misma lógica que el controlador en mapping()).
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, string>>  $sampleRows
     * @return array<string, string>
     */
    public function buildSuggestedColumnsMap(SupplierImport $import, array $columns, array $sampleRows): array
    {
        $targetFields = ImportTransformerService::TARGET_FIELDS;
        $suggestedMap = [];

        foreach ($import->supplier->supplierFieldMappings()->where('is_active', true)->orderBy('priority')->get() as $m) {
            $suggestedMap[$m->target_field] = $m->source_key;
        }

        $aliases = SupplierColumnAlias::orderBy('target_field')->get();
        foreach ($aliases as $a) {
            if (! isset($suggestedMap[$a->target_field]) && in_array($a->target_field, $targetFields, true)) {
                foreach ($columns as $col) {
                    if (stripos($col, $a->alias) !== false || stripos($a->alias, $col) !== false) {
                        $suggestedMap[$a->target_field] = $col;
                        break;
                    }
                }
            }
        }

        $profile = $this->profileResolver->resolve($import->supplier, $import);
        $profileSuggested = $profile->suggestMapping($import->supplier, $import, $columns, $sampleRows);

        $columnsMap = [];
        foreach ($targetFields as $field) {
            if (isset($suggestedMap[$field]) && in_array($suggestedMap[$field], $columns, true)) {
                $columnsMap[$field] = $suggestedMap[$field];
                continue;
            }
            if (isset($profileSuggested[$field]) && in_array($profileSuggested[$field], $columns, true)) {
                $columnsMap[$field] = $profileSuggested[$field];
                continue;
            }
            if (isset($suggestedMap[$field])) {
                $columnsMap[$field] = $suggestedMap[$field];
            }
        }

        $currentSnapshot = $import->mapping_snapshot ?? [];
        if (! empty($currentSnapshot['columns_map'])) {
            $columnsMap = array_merge($columnsMap, $currentSnapshot['columns_map']);
        }

        return $columnsMap;
    }

    /**
     * Guarda mapping_snapshot y persiste supplier_import_rows (misma lógica que saveMapping).
     *
     * @param  array<string, string>  $columnsMap
     */
    public function persistMappingAndRows(SupplierImport $import, array $columnsMap, string $filePath, ?int $savedByUserId = null): void
    {
        if ($this->importHasGeneratedCatalog($import)) {
            $this->resetService->resetToMappingState($import);
            $import->refresh();
        }

        $import->update([
            'status' => 'mapping',
            'mapping_snapshot' => [
                'columns_map' => $columnsMap,
                'mapping_entries' => array_values(array_map(fn ($target) => ['target' => $target, 'origin' => $columnsMap[$target]], array_keys($columnsMap))),
                'saved_at' => now()->toIso8601String(),
                'saved_by' => $savedByUserId,
            ],
        ]);

        $reader = $this->readerFactory->getReaderForType($import->file_type);
        $rows = $reader->readRows($filePath, null);

        DB::transaction(function () use ($import, $rows) {
            SupplierImportRow::where('supplier_import_id', $import->id)->delete();
            $import->update(['total_rows' => count($rows)]);

            $batch = [];
            foreach ($rows as $i => $raw) {
                $batch[] = [
                    'supplier_import_id' => $import->id,
                    'row_index' => $i + 1,
                    'raw_data' => json_encode($raw),
                    'status' => SupplierImportRow::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (count($batch) >= 500) {
                    SupplierImportRow::insert($batch);
                    $batch = [];
                }
            }
            if (! empty($batch)) {
                SupplierImportRow::insert($batch);
            }
        });
    }

    private function importHasGeneratedCatalog(SupplierImport $import): bool
    {
        return $import->normalizedProducts()->exists()
            || $import->normalizationRuns()->exists()
            || (int) $import->processed_rows > 0
            || (int) $import->error_rows > 0
            || in_array((string) $import->status, ['processed', 'processing', 'failed'], true);
    }
}
