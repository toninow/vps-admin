<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierImportRequest;
use App\Models\Project;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MpsfpImportController extends Controller
{
    public function __construct(
        protected FileTypeDetector $detector,
        protected FileReaderFactory $readerFactory
    ) {}

    public function index(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'importaciones');

        $imports = SupplierImport::with('supplier', 'user')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('catalog_year'), fn ($q) => $q->where('catalog_year', $request->integer('catalog_year')))
            ->when($request->filled('imported_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('imported_from')))
            ->when($request->filled('imported_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('imported_to')))
            ->latest()
            ->paginate(15);

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('imports.index', [
            'imports' => $imports,
            'suppliers' => $suppliers,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
        ]);
    }

    public function create(Project $project): View
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'create');

        return view('imports.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function store(Project $project, StoreSupplierImportRequest $request): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'create');

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
        Storage::disk('local')->deleteDirectory($dir);
        Storage::disk('local')->makeDirectory($dir);

        $path = Storage::disk('local')->putFileAs($dir, $file, 'file.' . $ext);

        if (! is_string($path) || $path === '' || ! Storage::disk('local')->exists($path)) {
            $import->delete();

            return redirect()
                ->back()
                ->withInput($request->except('file'))
                ->withErrors([
                    'file' => 'No se pudo guardar el archivo en el servidor. Revisa permisos de escritura e intentalo de nuevo.',
                ]);
        }

        $import->update(['file_path' => $path]);

        $realPath = Storage::disk('local')->path($path);
        $detectedFromContent = $this->detector->detectFromPath($realPath);
        if ($detectedFromContent !== $fileType && in_array($detectedFromContent, $allowed, true)) {
            $import->update(['file_type' => $detectedFromContent]);
        }

        return redirect()
            ->route('projects.mpsfp.imports.preview', [$project, $import])
            ->with('status', 'Archivo subido correctamente. Revise el preview y continúe al mapeo.');
    }

    public function show(Project $project, SupplierImport $import, ImportMappingValidationService $validationService): View
    {
        $this->ensureMpsfpAbility($project, 'importaciones');

        $import->load(['supplier', 'user']);
        $import->loadCount([
            'supplierImportRows',
            'normalizedProducts',
            'normalizationRuns',
        ]);
        $lastNormalizationRun = $import->normalizationRuns()
            ->latest('started_at')
            ->first();

        $validationReport = null;
        if ($import->status === 'processed' && (($import->normalized_products_count ?? 0) > 0)) {
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
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function preview(Project $project, SupplierImport $import): View|RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones');

        $path = Storage::disk('local')->path($import->file_path);
        if (! file_exists($path) || ! is_readable($path)) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'El archivo ya no está disponible en el servidor.']);
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
            $columns = $reader->getColumnNames($path);
            $previewRows = $reader->readRows($path, 15);
        } catch (\Throwable $e) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'Error al leer el archivo: ' . $e->getMessage()]);
        }

        if (empty($columns) && empty($previewRows)) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'El archivo está vacío o no se detectaron columnas.']);
        }

        return view('imports.preview', [
            'import' => $import,
            'columns' => $columns,
            'previewRows' => $previewRows,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function mapping(Project $project, SupplierImport $import): View|RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');

        $path = Storage::disk('local')->path($import->file_path);
        if (! file_exists($path) || ! is_readable($path)) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'El archivo ya no está disponible.']);
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
            $columns = $reader->getColumnNames($path);
        } catch (\Throwable $e) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'Error al leer el archivo: ' . $e->getMessage()]);
        }

        $targetFields = ImportTransformerService::TARGET_FIELDS;
        $aliases = \App\Models\SupplierColumnAlias::orderBy('target_field')->get();
        $suggestedMap = [];
        foreach ($import->supplier->supplierFieldMappings()->where('is_active', true)->orderBy('priority')->get() as $mapping) {
            $suggestedMap[$mapping->target_field] = $mapping->source_key;
        }
        foreach ($aliases as $alias) {
            if (! isset($suggestedMap[$alias->target_field]) && in_array($alias->target_field, $targetFields, true)) {
                foreach ($columns as $column) {
                    if (stripos($column, $alias->alias) !== false || stripos($alias->alias, $column) !== false) {
                        $suggestedMap[$alias->target_field] = $column;
                        break;
                    }
                }
            }
        }

        $sampleRows = [];
        $sampleLimit = $import->file_type === FileTypeDetector::TYPE_XML ? 20 : 120;
        try {
            $sampleRows = $reader->readRows($path, $sampleLimit);
        } catch (\Throwable $e) {
        }

        $profileResolver = app(\App\Services\Suppliers\SupplierProfileResolver::class);
        $profile = $profileResolver->resolve($import->supplier, $import);
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

        return view('imports.mapping', [
            'import' => $import,
            'columns' => $columns,
            'targetFields' => $targetFields,
            'columnsMap' => $columnsMap,
            'previewRows' => array_slice($sampleRows, 0, 5),
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function saveMapping(
        Project $project,
        Request $request,
        SupplierImport $import,
        ImportPipelineResetService $resetService
    ): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');
        $import->refresh();

        if ($import->pipeline_is_running) {
            return redirect()
                ->route('projects.mpsfp.imports.show', [$project, $import])
                ->withErrors(['pipeline' => 'La importación tiene un proceso en marcha. Espera a que termine o cancélalo antes de volver a guardar el mapeo.']);
        }

        $replacedExistingBatch = false;
        if ($this->importHasGeneratedCatalog($import)) {
            $resetService->resetToMappingState($import);
            $import->refresh();
            $replacedExistingBatch = true;
        }

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
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'El archivo ya no está disponible. No se pudieron guardar las filas.'])->with('status', 'Mapeo guardado; filas no persistidas.');
        }

        try {
            $reader = $this->readerFactory->getReaderForType($import->file_type);
        } catch (\Throwable $e) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['file' => 'Error al leer el archivo para guardar filas: ' . $e->getMessage()]);
        }

        try {
            DB::transaction(function () use ($import, $reader, $path) {
                SupplierImportRow::where('supplier_import_id', $import->id)->delete();

                $batch = [];
                $totalRows = 0;

                $persistRow = function (array $raw, int $rowIndex) use (&$batch, $import) {
                    $batch[] = [
                        'supplier_import_id' => $import->id,
                        'row_index' => $rowIndex,
                        'raw_data' => json_encode($raw),
                        'status' => SupplierImportRow::STATUS_PENDING,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        SupplierImportRow::insert($batch);
                        $batch = [];
                    }
                };

                if ($reader instanceof \App\Services\Import\XmlFileReader) {
                    $totalRows = $reader->streamRows($path, function (array $raw, int $rowIndex) use ($persistRow) {
                        $persistRow($raw, $rowIndex);
                    });
                } else {
                    $rows = $reader->readRows($path, null);
                    foreach ($rows as $i => $raw) {
                        $persistRow($raw, $i + 1);
                    }
                    $totalRows = count($rows);
                }

                if (! empty($batch)) {
                    SupplierImportRow::insert($batch);
                }

                $import->update(['total_rows' => $totalRows]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['db' => 'Error al guardar las filas: ' . $e->getMessage()]);
        }

        if ($launchFullPipeline) {
            if ($import->fresh()->pipeline_is_running) {
                return redirect()
                    ->route('projects.mpsfp.imports.show', [$project, $import])
                    ->withErrors(['pipeline' => 'La importación ya tiene un proceso en marcha.']);
            }

            $this->queueProcessPipeline($import->fresh());

            return redirect()
                ->route('projects.mpsfp.imports.show', [$project, $import])
                ->with('status', 'Mapeo guardado, filas persistidas y procesamiento completo encolado. El lote importará, normalizará, consolidará maestros, revisará EAN, preparará categorías y aprobará automáticamente lo seguro.');
        }

        $status = 'Mapeo guardado y ' . number_format((int) $import->fresh()->total_rows, 0, ',', '.') . ' filas persistidas. Puede procesar la importación.';
        if ($replacedExistingBatch) {
            $status = 'Mapeo guardado, lote anterior reemplazado y ' . number_format((int) $import->fresh()->total_rows, 0, ',', '.') . ' filas persistidas. Puede procesar la importación.';
        }

        return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->with('status', $status);
    }

    public function process(Project $project, Request $request, SupplierImport $import): RedirectResponse|JsonResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');

        if ($import->pipeline_is_running) {
            return $this->pipelineResponse(
                $request,
                $project,
                $import,
                'Esta importación ya se está procesando en segundo plano.',
                'error',
                409
            );
        }

        $this->queueProcessPipeline($import);

        return $this->pipelineResponse(
            $request,
            $project,
            $import->fresh(),
            'Procesamiento, normalización y cierre automático encolados. La barra de progreso se actualizará automáticamente.'
        );
    }

    public function normalize(Project $project, Request $request, SupplierImport $import): RedirectResponse|JsonResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');

        if ($import->status !== 'processed') {
            return $this->pipelineResponse(
                $request,
                $project,
                $import,
                'Solo se puede ejecutar la normalización avanzada sobre una importación ya procesada.',
                'error',
                422
            );
        }

        if ($import->pipeline_is_running) {
            return $this->pipelineResponse(
                $request,
                $project,
                $import,
                'La importación ya tiene un proceso en marcha.',
                'error',
                409
            );
        }

        $import->update([
            'pipeline_status' => 'queued',
            'pipeline_stage' => 'normalizing',
            'pipeline_total' => $import->normalizedProducts()->count(),
            'pipeline_processed' => 0,
            'pipeline_percent' => 0,
            'pipeline_message' => 'La normalización avanzada ha entrado en cola.',
            'pipeline_started_at' => null,
            'pipeline_finished_at' => null,
            'error_message' => null,
        ]);

        app(BackgroundArtisan::class)->run(
            ['imports:run-normalization', (string) $import->id],
            storage_path("logs/import-normalize-{$import->id}.log"),
            $this->pipelinePidFile($import)
        );

        return $this->pipelineResponse(
            $request,
            $project,
            $import->fresh(),
            'Reprocesado completo y cierre automático encolados. La barra de progreso se actualizará automáticamente.'
        );
    }

    public function cancel(
        Project $project,
        Request $request,
        SupplierImport $import,
        ImportPipelineResetService $resetService
    ): RedirectResponse|JsonResponse {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');

        if (! in_array($import->pipeline_status, ['queued', 'processing'], true)) {
            return $this->pipelineResponse(
                $request,
                $project,
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
            $project,
            $import->fresh(),
            $message
        );
    }

    public function status(Project $project, SupplierImport $import): JsonResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones');

        $latestRun = $import->normalizationRuns()->latest('id')->first();
        $payload = [
            'id' => $import->id,
            'status' => $import->status,
            'updated_at' => $import->updated_at?->toIso8601String(),
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
            'latest_run' => null,
        ];

        if ($latestRun) {
            $payload['latest_run'] = [
                'id' => $latestRun->id,
                'status' => $latestRun->status,
                'total_products' => $latestRun->total_products,
                'processed_products' => $latestRun->processed_products,
                'percent_complete' => $latestRun->percent_complete,
                'errors' => $latestRun->errors,
                'error_message' => $latestRun->error_message,
            ];
        }

        return response()->json($payload);
    }

    public function destroy(Project $project, SupplierImport $import): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'importaciones', 'process');

        $filename = $import->filename_original;
        if ($import->file_path) {
            Storage::disk('local')->deleteDirectory(dirname($import->file_path));
        }

        $import->delete();

        return redirect()->route('projects.mpsfp.imports.index', $project)->with('status', "Importación eliminada: {$filename}.");
    }

    protected function ensureMpsfpProject(Project $project): void
    {
        $this->authorize('view', $project);

        if ($project->slug !== 'mpsfp') {
            abort(404);
        }
    }

    protected function ensureMpsfpAbility(Project $project, string $section, string $ability = 'view'): void
    {
        $this->ensureMpsfpProject($project);

        if (! auth()->user()->canAccessMpsfpSection($section, $ability)) {
            abort(403);
        }
    }

    protected function mpsfpSections(Project $project): array
    {
        $capabilities = auth()->user()->mpsfpCapabilities()['sections'];

        return [
            'proveedores' => array_merge($capabilities['proveedores'], ['url' => route('projects.mpsfp.suppliers.index', $project)]),
            'importaciones' => array_merge($capabilities['importaciones'], ['url' => route('projects.mpsfp.imports.index', $project)]),
            'normalizados' => array_merge($capabilities['normalizados'], ['url' => route('projects.mpsfp.normalized.index', $project)]),
            'maestros' => array_merge($capabilities['maestros'], ['url' => route('projects.mpsfp.master.index', $project)]),
            'ean' => array_merge($capabilities['ean'], ['url' => route('projects.mpsfp.section', ['project' => $project, 'section' => 'ean'])]),
            'duplicados' => array_merge($capabilities['duplicados'], ['url' => route('projects.mpsfp.section', ['project' => $project, 'section' => 'duplicados'])]),
            'cruce_proveedores' => array_merge($capabilities['cruce_proveedores'], ['url' => route('projects.mpsfp.cross-suppliers.index', $project)]),
            'categorias' => array_merge($capabilities['categorias'], ['url' => route('projects.mpsfp.categories.review', $project)]),
            'exportacion' => array_merge($capabilities['exportacion'], ['url' => route('projects.mpsfp.export.index', $project)]),
        ];
    }

    private function pipelineResponse(
        Request $request,
        Project $project,
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
                'status_url' => route('projects.mpsfp.imports.status', [$project, $import]),
                'cancel_url' => route('projects.mpsfp.imports.cancel', [$project, $import]),
                'show_url' => route('projects.mpsfp.imports.show', [$project, $import]),
                'pipeline_status' => $import->pipeline_status,
                'pipeline_stage' => $import->pipeline_stage,
            ], $statusCode);
        }

        if ($type === 'success') {
            return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->with('status', $message);
        }

        return redirect()->route('projects.mpsfp.imports.show', [$project, $import])->withErrors(['process' => $message]);
    }

    private function pipelinePidFile(SupplierImport $import): string
    {
        return storage_path("logs/import-pipeline-{$import->id}.pid");
    }

    private function importHasGeneratedCatalog(SupplierImport $import): bool
    {
        return $import->normalizedProducts()->exists()
            || $import->normalizationRuns()->exists()
            || (int) $import->processed_rows > 0
            || (int) $import->error_rows > 0
            || in_array((string) $import->status, ['processed', 'processing', 'failed'], true);
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
