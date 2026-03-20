<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierImportRequest;
use App\Models\Supplier;
use App\Models\SupplierFieldMapping;
use App\Models\SupplierImport;
use App\Models\SupplierImportRow;
use App\Services\Import\FileReaderFactory;
use App\Services\Import\FileTypeDetector;
use App\Services\Import\ImportMappingValidationService;
use App\Services\Import\ImportPipelineResetService;
use App\Services\Import\ImportTransformerService;
use App\Support\BackgroundArtisan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierImportController extends Controller
{
    public function __construct(
        protected FileTypeDetector $detector,
        protected FileReaderFactory $readerFactory
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', SupplierImport::class);

        $imports = SupplierImport::with('supplier', 'user')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('catalog_year'), fn ($q) => $q->where('catalog_year', $request->integer('catalog_year')))
            ->when($request->filled('imported_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('imported_from')))
            ->when($request->filled('imported_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('imported_to')))
            ->latest()
            ->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('imports.index', compact('imports', 'suppliers'));
    }

    public function create()
    {
        $this->authorize('create', SupplierImport::class);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('imports.create', compact('suppliers'));
    }

    public function store(StoreSupplierImportRequest $request)
    {
        $this->authorize('create', SupplierImport::class);

        $file = $request->file('file');
        $fileType = $this->detector->detect($file);

        $allowed = [FileTypeDetector::TYPE_CSV, FileTypeDetector::TYPE_XLSX, FileTypeDetector::TYPE_XML];
        if (! in_array($fileType, $allowed, true)) {
            return redirect()->back()->withInput()->withErrors(['file' => 'Tipo de archivo no soportado. Use CSV, Excel (XLSX/XLS) o XML.']);
        }

        $import = SupplierImport::create([
            'supplier_id' => $request->validated('supplier_id'),
            'user_id' => $request->user()->id,
            'filename_original' => $file->getClientOriginalName(),
            'file_path' => '',
            'file_type' => $fileType,
            'catalog_year' => (int) ($request->validated('catalog_year') ?: now()->year),
            'status' => 'uploaded',
        ]);

        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: $fileType;
        $dir = "supplier-imports/{$import->id}";
        $path = $file->storeAs($dir, 'file.' . $ext, 'local');
        $import->update(['file_path' => $path]);

        $realPath = Storage::disk('local')->path($path);
        $detectedFromContent = $this->detector->detectFromPath($realPath);
        if ($detectedFromContent !== $fileType && in_array($detectedFromContent, $allowed, true)) {
            $import->update(['file_type' => $detectedFromContent]);
        }

        return redirect()->route('imports.preview', $import)->with('status', 'Archivo subido correctamente. Revise el preview y continúe al mapeo.');
    }

    public function show(SupplierImport $import, ImportMappingValidationService $validationService)
    {
        $this->authorize('view', $import);
        $import->load(['supplier', 'user', 'normalizationRuns']);
        $import->loadCount([
            'supplierImportRows',
            'normalizedProducts',
            'normalizationRuns',
        ]);

        $lastNormalizationRun = $import->normalizationRuns()
            ->latest('started_at')
            ->first();

        $validationReport = null;
        if ($import->status === 'processed' && $import->normalizedProducts()->exists()) {
            $validationReport = $validationService->reportForImport($import);
        }

        $errorProducts = $import->normalizedProducts()
            ->select(['id', 'name', 'supplier_reference', 'barcode_raw', 'barcode_status', 'ean_status', 'cost_price', 'price_tax_incl'])
            ->where(function ($query) {
                $query->whereIn('barcode_status', ['invalid_ean', 'missing'])
                    ->orWhereNull('cost_price')
                    ->orWhereNull('price_tax_incl')
                    ->orWhereColumn('price_tax_incl', '<', 'cost_price');
            })
            ->orderByRaw("CASE WHEN barcode_status = 'invalid_ean' THEN 0 WHEN barcode_status = 'missing' THEN 1 WHEN cost_price IS NULL THEN 2 WHEN price_tax_incl IS NULL THEN 3 WHEN price_tax_incl < cost_price THEN 4 ELSE 5 END")
            ->limit(12)
            ->get();

        return view('imports.show', [
            'import' => $import,
            'lastNormalizationRun' => $lastNormalizationRun,
            'validationReport' => $validationReport,
            'errorProducts' => $errorProducts,
        ]);
    }

    public function preview(SupplierImport $import)
    {
        $this->authorize('view', $import);

        $path = Storage::disk('local')->path($import->file_path);
        if (! file_exists($path) || ! is_readable($path)) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'El archivo ya no está disponible en el servidor.']);
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
            $columns = $reader->getColumnNames($path);
            $previewRows = $reader->readRows($path, 15);
        } catch (\Throwable $e) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'Error al leer el archivo: ' . $e->getMessage()]);
        }

        if (empty($columns) && empty($previewRows)) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'El archivo está vacío o no se detectaron columnas.']);
        }

        return view('imports.preview', compact('import', 'columns', 'previewRows'));
    }

    public function mapping(SupplierImport $import)
    {
        $this->authorize('update', $import);

        $path = Storage::disk('local')->path($import->file_path);
        if (! file_exists($path) || ! is_readable($path)) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'El archivo ya no está disponible.']);
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
            $columns = $reader->getColumnNames($path);
        } catch (\Throwable $e) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'Error al leer el archivo: ' . $e->getMessage()]);
        }

        $targetFields = ImportTransformerService::TARGET_FIELDS;
        $aliases = \App\Models\SupplierColumnAlias::orderBy('target_field')->get();
        $suggestedMap = [];
        foreach ($import->supplier->supplierFieldMappings()->where('is_active', true)->orderBy('priority')->get() as $m) {
            $suggestedMap[$m->target_field] = $m->source_key;
        }
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

        // Nueva capa de perfiles por proveedor
        $sampleRows = [];
        try {
            $sampleRows = $reader->readRows($path, 200);
        } catch (\Throwable $e) {
            // si falla lectura de filas, seguimos solo con headers/aliases
        }

        $profileResolver = app(\App\Services\Suppliers\SupplierProfileResolver::class);
        $profile = $profileResolver->resolve($import->supplier, $import);
        $profileSuggested = $profile->suggestMapping($import->supplier, $import, $columns, $sampleRows);

        // Combinar: prioridad mappings históricos, luego perfil, luego alias global
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

        $previewRows = array_slice($sampleRows, 0, 5);

        return view('imports.mapping', compact('import', 'columns', 'targetFields', 'columnsMap', 'previewRows'));
    }

    public function saveMapping(Request $request, SupplierImport $import)
    {
        $this->authorize('update', $import);
        $postMappingAction = (string) $request->input('post_mapping_action', 'save');
        $launchFullPipeline = $postMappingAction === 'process';

        $targetFields = ImportTransformerService::TARGET_FIELDS;
        $columnsMap = [];
        foreach ($targetFields as $field) {
            $source = $request->input("map_{$field}");
            if ($source !== null && trim((string) $source) !== '') {
                $columnsMap[$field] = trim((string) $source);
            }
        }

        $mappingEntries = [];
        foreach ($columnsMap as $target => $origin) {
            $mappingEntries[] = ['target' => $target, 'origin' => $origin];
        }

        $import->update([
            'status' => 'mapping',
            'mapping_snapshot' => [
                'columns_map' => $columnsMap,
                'mapping_entries' => $mappingEntries,
                'post_mapping_action' => $postMappingAction,
                'saved_at' => now()->toIso8601String(),
                'saved_by' => $request->user()?->id,
            ],
        ]);

        foreach ($columnsMap as $targetField => $sourceKey) {
            if ($sourceKey === '') {
                continue;
            }
            SupplierFieldMapping::updateOrCreate(
                [
                    'supplier_id' => $import->supplier_id,
                    'target_field' => $targetField,
                ],
                [
                    'source_key' => $sourceKey,
                    'transform' => null,
                    'priority' => 0,
                    'is_active' => true,
                ]
            );
        }

        $path = Storage::disk('local')->path($import->file_path);
        if (! file_exists($path)) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'El archivo ya no está disponible. No se pudieron guardar las filas.'])->with('status', 'Mapeo guardado; filas no persistidas.');
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
            $rows = $reader->readRows($path, null);
        } catch (\Throwable $e) {
            return redirect()->route('imports.show', $import)->withErrors(['file' => 'Error al leer el archivo para guardar filas: ' . $e->getMessage()]);
        }

        try {
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
        } catch (\Throwable $e) {
            return redirect()->route('imports.show', $import)->withErrors(['db' => 'Error al guardar las filas: ' . $e->getMessage()]);
        }

        if ($launchFullPipeline) {
            if ($import->fresh()->pipeline_is_running) {
                return redirect()
                    ->route('imports.show', $import)
                    ->withErrors(['pipeline' => 'La importación ya tiene un proceso en marcha.']);
            }

            $this->queueProcessPipeline($import->fresh());

            return redirect()
                ->route('imports.show', $import)
                ->with('status', 'Mapeo guardado, filas persistidas y procesamiento completo encolado. El lote importará, normalizará, consolidará maestros, revisará EAN, preparará categorías y aprobará automáticamente lo seguro.');
        }

        return redirect()->route('imports.show', $import)->with('status', 'Mapeo guardado y ' . count($rows) . ' filas persistidas. Puede procesar la importación.');
    }

    public function process(Request $request, SupplierImport $import)
    {
        $this->authorize('update', $import);

        if ($import->pipeline_is_running) {
            return $this->pipelineResponse(
                $request,
                $import,
                'Esta importación ya se está procesando en segundo plano.',
                'error',
                409
            );
        }

        $this->queueProcessPipeline($import);

        return $this->pipelineResponse(
            $request,
            $import->fresh(),
            'Procesamiento, normalización y cierre automático encolados. La barra de progreso se actualizará automáticamente.'
        );
    }

    public function cancel(Request $request, SupplierImport $import, ImportPipelineResetService $resetService)
    {
        $this->authorize('update', $import);

        if (! in_array($import->pipeline_status, ['queued', 'processing'], true)) {
            return $this->pipelineResponse(
                $request,
                $import,
                'No hay un proceso activo para cancelar en esta importación.',
                'error',
                409
            );
        }

        $import->update([
            'pipeline_status' => 'processing',
            'pipeline_message' => 'Cancelando el proceso y limpiando el lote...',
            'error_message' => null,
        ]);

        app(BackgroundArtisan::class)->terminate(
            $this->pipelinePidFile($import),
            [
                "artisan imports:run-process {$import->id}",
                "artisan imports:run-normalization {$import->id}",
            ]
        );

        $result = $resetService->resetToMappingState($import->fresh());
        $message = sprintf(
            'Proceso cancelado. Se eliminaron %d productos normalizados y %d maestros huérfanos del lote.',
            $result['normalized_deleted'] ?? 0,
            $result['master_deleted'] ?? 0
        );

        return $this->pipelineResponse(
            $request,
            $import->fresh(),
            $message
        );
    }

    public function destroy(SupplierImport $import): RedirectResponse
    {
        $this->authorize('delete', $import);

        $filename = $import->filename_original;
        if ($import->file_path) {
            Storage::disk('local')->deleteDirectory(dirname($import->file_path));
        }

        $import->delete();

        return redirect()->route('imports.index')->with('status', "Importación eliminada: {$filename}.");
    }

    public function status(SupplierImport $import): JsonResponse
    {
        $this->authorize('view', $import);

        $latestRun = $import->normalizationRuns()->latest('id')->first();

        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'pipeline_status' => $import->pipeline_status,
            'pipeline_stage' => $import->pipeline_stage,
            'pipeline_total' => $import->pipeline_total,
            'pipeline_processed' => $import->pipeline_processed,
            'pipeline_percent' => (float) $import->pipeline_percent,
            'pipeline_message' => $import->pipeline_message,
            'pipeline_started_at' => $import->pipeline_started_at,
            'pipeline_finished_at' => $import->pipeline_finished_at,
            'error_message' => $import->error_message,
            'processed_rows' => $import->processed_rows,
            'error_rows' => $import->error_rows,
            'normalized_products_count' => $import->normalizedProducts()->count(),
            'latest_run' => $latestRun ? [
                'id' => $latestRun->id,
                'status' => $latestRun->status,
                'total_products' => $latestRun->total_products,
                'processed_products' => $latestRun->processed_products,
                'percent_complete' => $latestRun->percent_complete,
                'errors' => $latestRun->errors,
                'error_message' => $latestRun->error_message,
            ] : null,
        ]);
    }

    private function pipelineResponse(
        Request $request,
        SupplierImport $import,
        string $message,
        string $type = 'success',
        int $statusCode = 200
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => $type === 'success',
                'type' => $type,
                'message' => $message,
                'status_url' => route('imports.status', $import),
                'cancel_url' => route('imports.cancel', $import),
                'show_url' => route('imports.show', $import),
                'pipeline_status' => $import->pipeline_status,
                'pipeline_stage' => $import->pipeline_stage,
            ], $statusCode);
        }

        if ($type === 'success') {
            return redirect()->route('imports.show', $import)->with('status', $message);
        }

        return redirect()->route('imports.show', $import)->withErrors(['process' => $message]);
    }

    private function pipelinePidFile(SupplierImport $import): string
    {
        return storage_path("logs/import-pipeline-{$import->id}.pid");
    }

    private function queueProcessPipeline(SupplierImport $import): void
    {
        $import->update([
            'pipeline_status' => 'queued',
            'pipeline_stage' => 'transforming',
            'pipeline_total' => (int) $import->total_rows,
            'pipeline_processed' => 0,
            'pipeline_percent' => 0,
            'pipeline_message' => 'La carga completa ha entrado en cola.',
            'pipeline_started_at' => null,
            'pipeline_finished_at' => null,
            'error_message' => null,
        ]);

        app(BackgroundArtisan::class)->run(
            ['imports:run-process', (string) $import->id],
            storage_path("logs/import-process-{$import->id}.log"),
            $this->pipelinePidFile($import)
        );
    }
}
