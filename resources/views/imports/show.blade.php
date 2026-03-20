@extends('layouts.app')

@section('title', 'Importación')
@section('page_title', 'Importación')

@section('content')
@php
    $canProcessImport = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.importaciones.actions.process', false)
        : auth()->user()->can('imports.process');
    $canDeleteImport = $canProcessImport;
    $pipelineIsActive = in_array($import->pipeline_status, ['queued', 'processing'], true);
    $statusUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.imports.status', [$mpsfpProject, $import])
        : route('imports.status', $import);
    $processUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.imports.process', [$mpsfpProject, $import])
        : route('imports.process', $import);
    $cancelUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.imports.cancel', [$mpsfpProject, $import])
        : route('imports.cancel', $import);
    $normalizeUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.imports.normalize', [$mpsfpProject, $import])
        : route('imports.normalize', $import);
    $normalizedIndexUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.normalized.index', $mpsfpProject)
        : route('products.normalized.index');
@endphp
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Ficha de importación',
            'subtitle' => 'Importación actual: ' . $import->filename_original,
        ])
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-medium">Errores:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    @if (session('import_process_messages') && count(session('import_process_messages')) > 0)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-medium">Detalle del último procesamiento:</p>
            <ul class="mt-2 max-h-48 list-inside list-disc overflow-y-auto">
                @foreach (session('import_process_messages') as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('normalization_result') && is_array(session('normalization_result')))
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            <p class="font-medium">Último reprocesado completo:</p>
            <ul class="mt-2 space-y-1">
                @foreach (session('normalization_result') as $step => $data)
                    <li><strong>{{ $step }}</strong>: @json($data)</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div
            class="mb-6 rounded-2xl border {{ $pipelineIsActive ? 'border-pink-200 bg-pink-50' : 'border-slate-200 bg-slate-50' }} p-5"
            id="import-pipeline-card"
            data-status-url="{{ $statusUrl }}"
            data-cancel-url="{{ $cancelUrl }}"
            data-pipeline-status="{{ $import->pipeline_status }}"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Carga de productos</p>
                    <h3 class="mt-2 text-base font-semibold text-[#555555]">
                        {{ $pipelineIsActive ? 'Procesando en segundo plano' : 'Estado del proceso de importación' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500" id="import-pipeline-message">
                        {{ $import->pipeline_message ?: 'La carga completa se lanzará en segundo plano para importar y normalizar sin bloquear el navegador ni saturar el servidor.' }}
                    </p>
                </div>
                <span class="status-badge {{ $pipelineIsActive ? 'status-pink' : 'status-gray' }}" id="import-pipeline-status">
                    {{ $import->pipeline_status ?: 'idle' }}
                </span>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500">
                    <span id="import-pipeline-stage">{{ $import->pipeline_stage ?: 'idle' }}</span>
                    <span><span id="import-pipeline-percent">{{ number_format((float) $import->pipeline_percent, 2) }}</span>%</span>
                </div>
                <div class="mt-2 h-3 overflow-hidden rounded-full bg-white/80 ring-1 ring-slate-200">
                    <div
                        id="import-pipeline-bar"
                        class="h-full rounded-full bg-[#E6007E] transition-all duration-500"
                        style="width: {{ min(100, max(0, (float) $import->pipeline_percent)) }}%;"
                    ></div>
                </div>
                <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">
                    <span>Actual: <strong id="import-pipeline-processed">{{ $import->pipeline_processed }}</strong></span>
                    <span>Total: <strong id="import-pipeline-total">{{ $import->pipeline_total }}</strong></span>
                    <span>Normalizados: <strong id="import-pipeline-normalized">{{ $import->normalized_products_count ?? 0 }}</strong></span>
                    <span>Errores: <strong id="import-pipeline-errors">{{ $import->error_rows ?? 0 }}</strong></span>
                </div>
                <p class="mt-3 hidden text-xs text-red-700" id="import-pipeline-error"></p>
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">Archivo</dt><dd class="text-sm text-[#555555]">{{ $import->filename_original }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Proveedor</dt><dd class="text-sm text-[#555555]">{{ $import->supplier->name ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $import->status }}</span></dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Tipo detectado</dt><dd class="text-sm text-[#555555]">{{ $import->file_type }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Año catálogo</dt><dd class="text-sm text-[#555555]">{{ $import->catalog_year ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Total filas</dt><dd class="text-sm text-[#555555]">{{ $import->total_rows ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Procesadas (OK)</dt><dd class="text-sm text-[#555555]">{{ $import->processed_rows ?? 0 }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Errores</dt><dd class="text-sm text-[#555555]">{{ $import->error_rows ?? 0 }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Fecha</dt><dd class="text-sm text-[#555555]">{{ $import->created_at->format('d/m/Y H:i') }}</dd></div>
        </dl>
        @if ($import->error_message)
            <p class="mt-4 text-sm text-red-600">{{ $import->error_message }}</p>
        @endif
        @if (isset($validationReport) && $validationReport !== null)
            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Validación del mapeo (normalized_products)</h3>
                <p class="mt-1 text-xs text-slate-500">Proveedor: {{ $validationReport['supplier_name'] }} · Perfil: {{ $validationReport['profile_logical_code'] ?? '—' }} · Total: {{ $validationReport['total'] }} productos</p>
                @if (! empty($validationReport['field_semantics']))
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        @foreach ($validationReport['field_semantics'] as $targetField => $semantic)
                            @continue(empty($semantic['source']))
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $targetField }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $semantic['label'] ?? 'Campo detectado' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Origen: <span class="font-mono text-slate-700">{{ $semantic['source'] }}</span></p>
                                @if (! empty($semantic['note']))
                                    <p class="mt-2 text-xs text-slate-600">{{ $semantic['note'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (! empty($validationReport['issue_summary']))
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&barcode_status=invalid_ean" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-600">EAN inválidos</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($validationReport['issue_summary']['barcode_invalid'] ?? 0) }}</p>
                        </a>
                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&barcode_status=non_ean" class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-600">UPC / GTIN / código interno</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($validationReport['issue_summary']['barcode_non_ean'] ?? 0) }}</p>
                        </a>
                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&price_issue=missing_cost" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-600">Sin precio compra</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($validationReport['issue_summary']['missing_cost_price'] ?? 0) }}</p>
                        </a>
                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&price_issue=missing_sale" class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-sm text-fuchsia-900">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-fuchsia-600">Sin precio venta</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($validationReport['issue_summary']['missing_sale_price'] ?? 0) }}</p>
                        </a>
                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&price_issue=sale_below_cost" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-600">Venta pública menor que proveedor</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($validationReport['issue_summary']['sale_below_cost_price'] ?? 0) }}</p>
                        </a>
                    </div>
                @endif
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-100/80">
                            <tr>
                                <th class="px-3 py-2 font-medium text-slate-700">Campo</th>
                                <th class="px-3 py-2 font-medium text-slate-700">Rellenados</th>
                                <th class="px-3 py-2 font-medium text-slate-700">Tasa %</th>
                                <th class="px-3 py-2 font-medium text-slate-700">Muestra 1</th>
                                <th class="px-3 py-2 font-medium text-slate-700">Muestra 2</th>
                                <th class="px-3 py-2 font-medium text-slate-700">Incidencias</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($validationReport['fields'] ?? [] as $field => $data)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-slate-700">{{ $field }}</td>
                                    <td class="px-3 py-2">{{ $data['filled'] }}/{{ $validationReport['total'] }}</td>
                                    <td class="px-3 py-2">{{ $data['rate'] }}%</td>
                                    <td class="max-w-[200px] truncate px-3 py-2 text-slate-600" title="{{ $data['samples'][0] ?? '' }}">{{ $data['samples'][0] ?? '—' }}</td>
                                    <td class="max-w-[200px] truncate px-3 py-2 text-slate-600" title="{{ $data['samples'][1] ?? '' }}">{{ $data['samples'][1] ?? '—' }}</td>
                                    <td class="max-w-[180px] px-3 py-2 text-xs text-amber-700">{{ implode('; ', array_slice($data['issues'] ?? [], 0, 2)) ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (isset($errorProducts) && $errorProducts->isNotEmpty())
            <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-rose-700">Productos con revisión real pendiente</h3>
                        <p class="mt-1 text-xs text-rose-600">Aquí solo salen incidencias materiales: EAN inválido o falta de precio de compra/venta.</p>
                    </div>
                    <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}" class="text-sm font-semibold text-rose-700 hover:underline">Ver lote completo</a>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-rose-200 text-left text-sm">
                        <thead class="bg-white/70">
                            <tr>
                                <th class="px-3 py-2 font-medium text-rose-800">Producto</th>
                                <th class="px-3 py-2 font-medium text-rose-800">Código</th>
                                <th class="px-3 py-2 font-medium text-rose-800">Precio proveedor</th>
                                <th class="px-3 py-2 font-medium text-rose-800">Precio venta al público</th>
                                <th class="px-3 py-2 font-medium text-rose-800">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100">
                            @foreach ($errorProducts as $product)
                                @php
                                    $issueReason = match (true) {
                                        $product->barcode_status === 'invalid_ean' => 'EAN inválido',
                                        $product->barcode_status === 'missing' => 'Sin código',
                                        $product->cost_price === null && $product->price_tax_incl === null => 'Sin precio compra ni venta',
                                        $product->cost_price === null => 'Falta precio compra',
                                        $product->price_tax_incl === null => 'Falta precio venta',
                                        $product->cost_price !== null && $product->price_tax_incl !== null && (float) $product->price_tax_incl < (float) $product->cost_price => 'Venta pública menor que proveedor',
                                        default => 'Revisión manual',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-slate-800">
                                        <a href="{{ $normalizedIndexUrl }}?supplier_import_id={{ $import->id }}&search={{ urlencode($product->supplier_reference ?: $product->name) }}" class="font-medium hover:underline">
                                            {{ $product->name ?: 'Producto sin nombre' }}
                                        </a>
                                        @if ($product->supplier_reference)
                                            <p class="text-xs text-slate-500">{{ $product->supplier_reference }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $product->barcode_raw ?: '—' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $product->cost_price !== null ? number_format((float) $product->cost_price, 4) : '' }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $product->price_tax_incl !== null ? number_format((float) $product->price_tax_incl, 4) : '' }}</td>
                                    <td class="px-3 py-2 text-xs font-semibold text-rose-700">{{ $issueReason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            @if (in_array($import->status, ['uploaded', 'mapping'], true))
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.preview', [$mpsfpProject, $import]) : route('imports.preview', $import) }}" class="btn-secondary">Preview</a>
                @if ($canProcessImport)
                    <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.mapping', [$mpsfpProject, $import]) : route('imports.mapping', $import) }}" class="btn-secondary">Mapeo</a>
                @else
                    <span class="btn-disabled">Mapeo bloqueado</span>
                @endif
            @endif
            @if ($canProcessImport && $import->status === 'mapping' && $import->total_rows > 0 && $import->mapping_snapshot)
                <form action="{{ $processUrl }}" method="POST" class="inline" data-pipeline-launch data-pipeline-kind="process">
                    @csrf
                    <button type="submit" class="btn-primary" data-pipeline-trigger-button {{ $pipelineIsActive ? 'disabled' : '' }}>
                        {{ $pipelineIsActive ? 'Proceso en marcha...' : 'Procesar y normalizar' }}
                    </button>
                </form>
            @endif
            @if ($canDeleteImport)
                <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.destroy', [$mpsfpProject, $import]) : route('imports.destroy', $import) }}" method="POST" class="inline" onsubmit="return confirm('Se eliminará la importación, sus filas y sus productos normalizados asociados. ¿Continuar?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-link-muted">Eliminar importación</button>
                </form>
            @endif
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.index', $mpsfpProject) : route('imports.index') }}" class="btn-link-muted">Volver al listado</a>
        </div>
    </div>

    @if(isset($lastNormalizationRun))
        <div
            class="rounded-lg border border-indigo-200 bg-indigo-50 p-6 text-sm text-indigo-900 shadow-sm"
            id="normalization-run-card"
            data-run-id="{{ $lastNormalizationRun->id }}"
            data-run-status="{{ $lastNormalizationRun->status }}"
        >
            <p class="font-medium mb-2">Estado del último reprocesado completo</p>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Estado</dt>
                    <dd class="mt-1 text-sm">
                        <span
                            class="rounded-full bg-white/70 px-2 py-0.5 text-xs font-semibold text-indigo-700"
                            id="normalization-run-status"
                        >
                            {{ $lastNormalizationRun->status }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Progreso</dt>
                    <dd class="mt-1 text-sm">
                        <span id="normalization-run-processed">{{ $lastNormalizationRun->processed_products }}</span>
                        /
                        <span id="normalization-run-total">{{ $lastNormalizationRun->total_products }}</span>
                        (<span id="normalization-run-percent">{{ $lastNormalizationRun->percent_complete }}</span>%)
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Errores</dt>
                    <dd class="mt-1 text-sm">
                        <span id="normalization-run-errors">{{ $lastNormalizationRun->errors }}</span>
                    </dd>
                </div>
            </dl>
            @if ($lastNormalizationRun->error_message)
                <p class="mt-3 text-xs text-red-700" id="normalization-run-error-message">
                    Último error: <span id="normalization-run-error-text">{{ $lastNormalizationRun->error_message }}</span>
                </p>
            @else
            <p class="mt-3 text-xs text-red-700 hidden" id="normalization-run-error-message">
                Último error: <span id="normalization-run-error-text"></span>
            </p>
            @endif
            <p class="mt-2 text-xs text-indigo-700 hidden" id="normalization-run-warning"></p>
        </div>
    @endif
</div>

<div id="import-progress-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4 py-6">
    <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[1.75rem] border border-white/10 bg-white shadow-2xl">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Carga por lotes</p>
                    <h3 id="import-progress-modal-title" class="mt-1 text-lg font-semibold text-[#555555]">Procesando importación</h3>
                    <p id="import-progress-modal-context" class="mt-1 text-xs font-medium uppercase tracking-[0.14em] text-slate-400">
                        {{ $import->supplier->name ?? 'Proveedor' }} · {{ $import->filename_original }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">Mantén esta pantalla abierta para seguir el avance en tiempo real.</p>
                </div>
                <div class="flex items-start gap-2 self-start">
                    <span id="import-progress-modal-status" class="status-badge status-pink">Procesando</span>
                    <button
                        type="button"
                        data-import-progress-close
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-500 transition hover:border-slate-300 hover:text-slate-700"
                        aria-label="Cerrar modal de progreso"
                        title="Cerrar"
                    >
                        X
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-5 px-5 py-5">
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs font-medium text-slate-500">
                    <span id="import-progress-modal-stage">Preparando lote...</span>
                    <span><span id="import-progress-modal-percent">0.00</span>%</span>
                </div>
                <div class="mt-3 h-4 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                    <div id="import-progress-modal-bar" class="h-full rounded-full bg-[#E6007E] transition-all duration-500" style="width: 0%;"></div>
                </div>
                <p id="import-progress-modal-message" class="mt-3 text-sm text-slate-600">El sistema está preparando la ejecución en segundo plano.</p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Actual</p>
                    <p id="import-progress-modal-processed" class="mt-2 text-lg font-semibold text-slate-800">0</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Total</p>
                    <p id="import-progress-modal-total" class="mt-2 text-lg font-semibold text-slate-800">0</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Normalizados</p>
                    <p id="import-progress-modal-normalized" class="mt-2 text-lg font-semibold text-slate-800">0</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Errores</p>
                    <p id="import-progress-modal-errors" class="mt-2 text-lg font-semibold text-slate-800">0</p>
                </div>
            </div>

            <div id="import-progress-modal-error-wrap" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <strong>Error:</strong>
                <span id="import-progress-modal-error"></span>
            </div>

            <div id="import-progress-modal-finished" class="hidden rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <strong>Proceso finalizado.</strong>
                <span id="import-progress-modal-finished-text">La vista se actualizará automáticamente en unos segundos.</span>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">Si minimizas el modal, el lote seguirá ejecutándose y la ficha continuará actualizando el progreso.</p>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button type="button" id="import-progress-modal-cancel" class="btn-link-muted text-red-600">Cancelar proceso</button>
                <button type="button" id="import-progress-modal-close" class="btn-secondary">Seguir en la ficha</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        (function () {
            const importCard = document.getElementById('import-pipeline-card');
            if (!importCard) {
                return;
            }

            const importStatusEl = document.getElementById('import-pipeline-status');
            const importMessageEl = document.getElementById('import-pipeline-message');
            const importStageEl = document.getElementById('import-pipeline-stage');
            const importPercentEl = document.getElementById('import-pipeline-percent');
            const importBarEl = document.getElementById('import-pipeline-bar');
            const importProcessedEl = document.getElementById('import-pipeline-processed');
            const importTotalEl = document.getElementById('import-pipeline-total');
            const importNormalizedEl = document.getElementById('import-pipeline-normalized');
            const importErrorsEl = document.getElementById('import-pipeline-errors');
            const importErrorEl = document.getElementById('import-pipeline-error');

            const modal = document.getElementById('import-progress-modal');
            const modalTitleEl = document.getElementById('import-progress-modal-title');
            const modalContextEl = document.getElementById('import-progress-modal-context');
            const modalStatusEl = document.getElementById('import-progress-modal-status');
            const modalStageEl = document.getElementById('import-progress-modal-stage');
            const modalPercentEl = document.getElementById('import-progress-modal-percent');
            const modalBarEl = document.getElementById('import-progress-modal-bar');
            const modalMessageEl = document.getElementById('import-progress-modal-message');
            const modalProcessedEl = document.getElementById('import-progress-modal-processed');
            const modalTotalEl = document.getElementById('import-progress-modal-total');
            const modalNormalizedEl = document.getElementById('import-progress-modal-normalized');
            const modalErrorsEl = document.getElementById('import-progress-modal-errors');
            const modalErrorWrapEl = document.getElementById('import-progress-modal-error-wrap');
            const modalErrorEl = document.getElementById('import-progress-modal-error');
            const modalFinishedEl = document.getElementById('import-progress-modal-finished');
            const modalFinishedTextEl = document.getElementById('import-progress-modal-finished-text');
            const modalCloseButtons = document.querySelectorAll('[data-import-progress-close], #import-progress-modal-close');
            const launchForms = document.querySelectorAll('[data-pipeline-launch]');
            const normalizationCard = document.getElementById('normalization-run-card');
            const modalCancelButton = document.getElementById('import-progress-modal-cancel');

            let importStatusUrl = importCard.getAttribute('data-status-url');
            let importCancelUrl = importCard.getAttribute('data-cancel-url');
            let importPipelineStatus = importCard.getAttribute('data-pipeline-status') || 'idle';
            let pollTimerId = null;
            let reloadTimerId = null;
            let modalMode = 'process';
            let isSubmitting = false;
            let isCancelling = false;
            const importContextLabel = @json(($import->supplier->name ?? 'Proveedor') . ' · ' . $import->filename_original);

            const statusClasses = {
                queued: 'status-amber',
                processing: 'status-pink',
                completed: 'status-green',
                failed: 'status-red',
                idle: 'status-gray',
            };

            const statusLabels = {
                queued: 'En cola',
                processing: 'Procesando',
                completed: 'Completado',
                failed: 'Fallido',
                idle: 'Sin iniciar',
            };

            const stageLabels = {
                transforming: 'Transformando filas importadas',
                normalizing: 'Normalizando catálogo, etiquetas y categorías',
                finalizing: 'Consolidando maestros y cerrando la automatización',
                idle: 'Preparando lote',
            };

            const titleByMode = {
                process: 'Procesando y normalizando importación',
                normalize: 'Reprocesando y cerrando importación',
            };

            const buttonLabelByKind = {
                process: { idle: 'Procesar y normalizar', busy: 'Proceso en marcha...' },
                normalize: { idle: 'Reprocesar y cerrar automático', busy: 'Proceso en marcha...' },
            };

            const setBadgeClass = (element, status) => {
                if (!element) return;
                element.className = `status-badge ${statusClasses[status] || statusClasses.idle}`;
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            };

            const setLaunchButtonsDisabled = (disabled) => {
                launchForms.forEach((form) => {
                    const button = form.querySelector('[data-pipeline-trigger-button]');
                    if (!button) return;

                    const kind = form.getAttribute('data-pipeline-kind') || 'process';
                    const labels = buttonLabelByKind[kind] || buttonLabelByKind.process;
                    button.disabled = disabled;
                    button.textContent = disabled ? labels.busy : labels.idle;
                });
            };

            const setModalState = (data) => {
                const latestRun = data.latest_run || null;
                const status = data.pipeline_status || importPipelineStatus || 'idle';
                const stage = data.pipeline_stage || 'idle';
                const useNormalizationProgress = stage === 'normalizing' && latestRun;
                const visibleProcessed = useNormalizationProgress ? (latestRun.processed_products ?? 0) : (data.pipeline_processed ?? 0);
                const visibleTotal = useNormalizationProgress ? (latestRun.total_products ?? 0) : (data.pipeline_total ?? 0);
                const visiblePercent = useNormalizationProgress ? Number(latestRun.percent_complete || 0) : Number(data.pipeline_percent || 0);
                const visibleErrors = useNormalizationProgress ? (latestRun.errors ?? 0) : (data.error_rows ?? 0);
                const hasError = !!data.error_message;
                const isActive = ['queued', 'processing'].includes(status);

                importPipelineStatus = status;

                const stageText = status === 'queued'
                    ? 'Esperando turno en la cola de lotes'
                    : (stageLabels[stage] || stage);
                const messageText = data.pipeline_message || 'El sistema está preparando la ejecución en segundo plano.';
                const percentText = visiblePercent.toFixed(2);

                setBadgeClass(importStatusEl, status);
                importStatusEl.textContent = statusLabels[status] || status;
                importMessageEl.textContent = messageText;
                importStageEl.textContent = stageText;
                importPercentEl.textContent = percentText;
                importBarEl.style.width = `${Math.min(100, Math.max(0, visiblePercent))}%`;
                importBarEl.classList.toggle('animate-pulse', status === 'queued' && visiblePercent === 0);
                importProcessedEl.textContent = visibleProcessed;
                importTotalEl.textContent = visibleTotal;
                importNormalizedEl.textContent = data.normalized_products_count ?? 0;
                importErrorsEl.textContent = visibleErrors;
                importErrorEl.classList.toggle('hidden', !hasError);
                importErrorEl.textContent = hasError ? data.error_message : '';

                modalTitleEl.textContent = titleByMode[modalMode] || titleByMode.process;
                if (modalContextEl) {
                    modalContextEl.textContent = importContextLabel;
                }
                setBadgeClass(modalStatusEl, status);
                modalStatusEl.textContent = statusLabels[status] || status;
                modalStageEl.textContent = stageText;
                modalPercentEl.textContent = percentText;
                modalBarEl.style.width = `${Math.min(100, Math.max(0, visiblePercent))}%`;
                modalBarEl.classList.toggle('animate-pulse', status === 'queued' && visiblePercent === 0);
                modalMessageEl.textContent = messageText;
                modalProcessedEl.textContent = visibleProcessed;
                modalTotalEl.textContent = visibleTotal;
                modalNormalizedEl.textContent = data.normalized_products_count ?? 0;
                modalErrorsEl.textContent = visibleErrors;
                modalErrorWrapEl.classList.toggle('hidden', !hasError);
                modalErrorEl.textContent = hasError ? data.error_message : '';
                modalFinishedEl.classList.toggle('hidden', status !== 'completed');
                if (status === 'completed' && modalFinishedTextEl) {
                    modalFinishedTextEl.textContent = `${importContextLabel} ha terminado correctamente. ${visibleProcessed} de ${visibleTotal} productos completados.`;
                }
                if (modalCancelButton) {
                    modalCancelButton.classList.toggle('hidden', !isActive);
                    modalCancelButton.disabled = !isActive || isCancelling;
                    modalCancelButton.textContent = isCancelling ? 'Cancelando...' : 'Cancelar proceso';
                }

                setLaunchButtonsDisabled(isActive);

                if (normalizationCard && latestRun) {
                    const normalizationStatusEl = document.getElementById('normalization-run-status');
                    const normalizationProcessedEl = document.getElementById('normalization-run-processed');
                    const normalizationTotalEl = document.getElementById('normalization-run-total');
                    const normalizationPercentEl = document.getElementById('normalization-run-percent');
                    const normalizationErrorsEl = document.getElementById('normalization-run-errors');
                    const normalizationErrorMessageWrapper = document.getElementById('normalization-run-error-message');
                    const normalizationErrorMessageText = document.getElementById('normalization-run-error-text');
                    const normalizationWarningEl = document.getElementById('normalization-run-warning');

                    normalizationCard.setAttribute('data-run-status', latestRun.status || 'pending');
                    if (normalizationStatusEl) normalizationStatusEl.textContent = latestRun.status || 'pending';
                    if (normalizationProcessedEl) normalizationProcessedEl.textContent = latestRun.processed_products ?? 0;
                    if (normalizationTotalEl) normalizationTotalEl.textContent = latestRun.total_products ?? 0;
                    if (normalizationPercentEl) normalizationPercentEl.textContent = latestRun.percent_complete ?? 0;
                    if (normalizationErrorsEl) normalizationErrorsEl.textContent = latestRun.errors ?? 0;
                    if (normalizationWarningEl) {
                        normalizationWarningEl.classList.add('hidden');
                        normalizationWarningEl.textContent = '';
                    }

                    if (normalizationErrorMessageWrapper && normalizationErrorMessageText) {
                        if (latestRun.error_message) {
                            normalizationErrorMessageWrapper.classList.remove('hidden');
                            normalizationErrorMessageText.textContent = latestRun.error_message;
                        } else {
                            normalizationErrorMessageWrapper.classList.add('hidden');
                            normalizationErrorMessageText.textContent = '';
                        }
                    }
                }
            };

            const stopPolling = () => {
                if (pollTimerId) {
                    clearTimeout(pollTimerId);
                    pollTimerId = null;
                }
            };

            const scheduleReload = () => {
                if (reloadTimerId) return;
                reloadTimerId = window.setTimeout(() => window.location.reload(), 6500);
            };

            const pollImportStatus = async () => {
                try {
                    const response = await fetch(importStatusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(`Estado HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    setModalState(data);

                    const latestRunIsActive = data.latest_run && data.latest_run.status === 'running';
                    if (['queued', 'processing'].includes(data.pipeline_status) || latestRunIsActive) {
                        pollTimerId = window.setTimeout(pollImportStatus, 2500);
                    } else if (data.pipeline_status === 'completed') {
                        scheduleReload();
                    } else if (data.pipeline_status === 'idle') {
                        scheduleReload();
                    }
                } catch (error) {
                    importErrorEl.classList.remove('hidden');
                    importErrorEl.textContent = 'No se pudo actualizar el progreso en tiempo real.';
                    modalErrorWrapEl.classList.remove('hidden');
                    modalErrorEl.textContent = 'No se pudo actualizar el progreso en tiempo real.';
                    pollTimerId = window.setTimeout(pollImportStatus, 4000);
                }
            };

            const startPolling = () => {
                stopPolling();
                pollImportStatus();
            };

            const showLaunchingState = (kind) => {
                modalMode = kind;
                openModal();
                setModalState({
                    pipeline_status: 'queued',
                    pipeline_stage: kind === 'normalize' ? 'normalizing' : 'transforming',
                    pipeline_processed: 0,
                    pipeline_total: 0,
                    pipeline_percent: 0,
                    pipeline_message: 'Encolando el lote y reservando turno de ejecución...',
                    normalized_products_count: {{ (int) ($import->normalized_products_count ?? 0) }},
                    error_rows: {{ (int) ($import->error_rows ?? 0) }},
                    error_message: '',
                    latest_run: null,
                });
            };

            launchForms.forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    if (isSubmitting || importPipelineStatus === 'processing' || importPipelineStatus === 'queued') {
                        openModal();
                        startPolling();
                        return;
                    }

                    const kind = form.getAttribute('data-pipeline-kind') || 'process';
                    const csrf = form.querySelector('input[name="_token"]')?.value;
                    isSubmitting = true;
                    showLaunchingState(kind);
                    setLaunchButtonsDisabled(true);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf || '',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => null);
                        if (!response.ok || !data || data.ok === false) {
                            throw new Error(data?.message || 'No se pudo iniciar el proceso por lotes.');
                        }

                        importStatusUrl = data.status_url || importStatusUrl;
                        importCancelUrl = data.cancel_url || importCancelUrl;
                        importPipelineStatus = data.pipeline_status || 'queued';
                        modalMessageEl.textContent = data.message || 'Proceso encolado correctamente.';
                        startPolling();
                    } catch (error) {
                        setLaunchButtonsDisabled(false);
                        importPipelineStatus = 'failed';
                        modalFinishedEl.classList.add('hidden');
                        modalErrorWrapEl.classList.remove('hidden');
                        modalErrorEl.textContent = error.message || 'No se pudo iniciar el proceso por lotes.';
                        modalMessageEl.textContent = 'El lote no pudo arrancar. Revisa el mensaje y vuelve a intentarlo.';
                        setBadgeClass(modalStatusEl, 'failed');
                        modalStatusEl.textContent = statusLabels.failed;
                    } finally {
                        isSubmitting = false;
                    }
                });
            });

            modalCloseButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            if (modalCancelButton) {
                modalCancelButton.addEventListener('click', async () => {
                    if (isCancelling || !importCancelUrl) {
                        return;
                    }

                    const confirmed = window.confirm('Se cancelará el lote y se eliminará todo lo generado por este proceso. ¿Continuar?');
                    if (!confirmed) {
                        return;
                    }

                    isCancelling = true;
                    modalCancelButton.disabled = true;
                    modalCancelButton.textContent = 'Cancelando...';
                    modalErrorWrapEl.classList.add('hidden');
                    modalFinishedEl.classList.add('hidden');
                    modalMessageEl.textContent = 'Cancelando el lote y limpiando los datos generados...';

                    try {
                        const csrf = document.querySelector('input[name="_token"]')?.value || '';
                        const response = await fetch(importCancelUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                            credentials: 'same-origin',
                        });

                        const data = await response.json().catch(() => null);
                        if (!response.ok || !data || data.ok === false) {
                            throw new Error(data?.message || 'No se pudo cancelar el proceso.');
                        }

                        importPipelineStatus = 'idle';
                        setModalState({
                            pipeline_status: 'completed',
                            pipeline_stage: 'idle',
                            pipeline_processed: 0,
                            pipeline_total: 0,
                            pipeline_percent: 100,
                            pipeline_message: data.message || 'Proceso cancelado y lote limpiado.',
                            normalized_products_count: 0,
                            error_rows: 0,
                            error_message: '',
                            latest_run: null,
                        });
                        if (modalFinishedTextEl) {
                            modalFinishedTextEl.textContent = data.message || 'Proceso cancelado y lote limpiado.';
                        }
                        scheduleReload();
                    } catch (error) {
                        modalErrorWrapEl.classList.remove('hidden');
                        modalErrorEl.textContent = error.message || 'No se pudo cancelar el proceso.';
                    } finally {
                        isCancelling = false;
                        if (modalCancelButton) {
                            modalCancelButton.disabled = false;
                            modalCancelButton.textContent = 'Cancelar proceso';
                        }
                    }
                });
            }

            if (['queued', 'processing'].includes(importPipelineStatus)) {
                openModal();
                startPolling();
            } else if (normalizationCard && normalizationCard.getAttribute('data-run-status') === 'running') {
                modalMode = 'normalize';
                openModal();
                startPolling();
            }
        })();
    </script>
@endpush
