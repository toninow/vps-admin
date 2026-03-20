@extends('layouts.app')

@section('title', 'Mapeo de columnas')
@section('page_title', 'Mapeo de columnas')

@section('content')
@php
    $mappingCompletion = count(array_filter($columnsMap ?? []));
    $mappingPercent = max(0, min(100, (int) round(($mappingCompletion / max(count($targetFields), 1)) * 100)));
    $mappingSupplierName = $import->supplier->name ?? 'Proveedor';
    $mappingFileName = $import->filename_original;
@endphp

<div class="space-y-6">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Mapeo de columnas',
            'subtitle' => 'Valida qué columna del archivo alimenta cada campo interno antes de persistir filas y lanzar el proceso.',
        ])
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <div>
                <p class="font-bold">No se pudo guardar el mapeo</p>
                <ul class="mt-2 list-inside list-disc text-sm">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="mpsfp-shell p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Archivo en edición</p>
                <h3 class="mt-2 font-headline text-2xl font-extrabold tracking-tight text-mptext">{{ $import->filename_original }}</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Proveedor: <span class="font-semibold text-mptext">{{ $import->supplier->name ?? '—' }}</span>
                    · Tipo: <span class="font-semibold text-mptext">{{ strtoupper($import->file_type) }}</span>
                    · Año: <span class="font-semibold text-mptext">{{ $import->catalog_year ?? '—' }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.preview', [$mpsfpProject, $import]) : route('imports.preview', $import) }}" class="btn-secondary">Volver al preview</a>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.show', [$mpsfpProject, $import]) : route('imports.show', $import) }}" class="btn-secondary">Detalle de importación</a>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Campos internos</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-mptext">{{ count($targetFields) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Columnas detectadas</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-mptext">{{ count($columns) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Auto-mapeo sugerido</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-[#E6007E]">{{ $mappingCompletion }}/{{ count($targetFields) }}</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#f1e4ea]">
                    <div class="h-full rounded-full bg-[#E6007E]" style="width: {{ $mappingPercent }}%"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.mapping.store', [$mpsfpProject, $import]) : route('imports.mapping.store', $import) }}" method="POST" class="mpsfp-panel p-6" id="import-mapping-form">
            @csrf

            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Configuración de columnas</p>
                    <h3 class="mt-2 font-headline text-xl font-extrabold tracking-tight text-mptext">Asigna el origen de cada campo</h3>
                </div>
                <span class="mpsfp-pill status-blue">{{ $mappingCompletion }} vinculadas</span>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($targetFields as $field)
                    @php
                        $selected = $columnsMap[$field] ?? '';
                        $isRecommended = $field === 'name';
                        $isStock = $field === 'quantity';
                    @endphp
                    <div class="grid gap-4 rounded-2xl border border-mpborder bg-[#fbfaf9] p-4 lg:grid-cols-[1fr_auto_1fr] lg:items-center">
                        <div class="rounded-2xl border border-white bg-[#f3f0f0] px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-mptext">{{ $field }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        @if ($isRecommended)
                                            Campo prioritario para crear productos normalizados.
                                        @elseif ($isStock)
                                            Este valor será el stock de origen en la capa normalizada.
                                        @else
                                            Campo interno del catálogo y de la futura exportación.
                                        @endif
                                    </p>
                                </div>
                                <span class="material-symbols-outlined text-gray-400">description</span>
                            </div>
                        </div>

                        <div class="hidden lg:flex justify-center">
                            <span class="material-symbols-outlined text-[#E6007E]" style="font-variation-settings: 'FILL' 1;">arrow_forward</span>
                        </div>

                        <div class="relative">
                            <select name="map_{{ $field }}" class="w-full rounded-2xl border-gray-300 bg-white py-3 pl-4 pr-10 text-sm font-semibold text-mptext shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E]">
                                <option value="">— No mapear —</option>
                                @foreach ($columns as $col)
                                    <option value="{{ $col }}" {{ $selected === $col ? 'selected' : '' }}>{{ $col }}</option>
                                @endforeach
                            </select>
                            @if ($selected !== '')
                                <p class="mt-2 text-xs text-[#E6007E]">Sugerencia activa: {{ $selected }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <button type="submit" name="post_mapping_action" value="save" class="btn-secondary" data-mapping-submit-label="Guardando mapeo y filas">Guardar mapeo y filas</button>
                <button type="submit" name="post_mapping_action" value="process" class="btn-primary" data-mapping-submit-label="Guardando y lanzando proceso completo">Guardar y procesar + normalizar</button>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.preview', [$mpsfpProject, $import]) : route('imports.preview', $import) }}" class="btn-secondary" data-import-activity-link data-activity-title="Volviendo al preview" data-activity-stage="Recargando muestra del archivo..." data-activity-message="La aplicación está preparando otra vez el preview para este lote.">Volver al preview</a>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.show', [$mpsfpProject, $import]) : route('imports.show', $import) }}" class="btn-link-muted">Ver importación</a>
            </div>
        </form>

        <div class="space-y-6">
            <section class="mpsfp-panel overflow-hidden">
                <div class="border-b border-mpborder bg-[#f7f5f5] px-6 py-4">
                    <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Vista previa</p>
                    <h3 class="mt-2 font-headline text-lg font-extrabold tracking-tight text-mptext">Primeras 5 filas detectadas</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="mpsfp-data-table min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                @foreach (collect($columns)->take(4) as $column)
                                    <th class="px-4 py-3 text-left">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse (($previewRows ?? []) as $row)
                                <tr>
                                    @foreach (collect($columns)->take(4) as $column)
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit((string) data_get($row, $column, '—'), 60) }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ min(count($columns), 4) ?: 1 }}" class="px-4 py-8 text-center text-sm text-gray-500">No hay filas de muestra disponibles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mpsfp-soft-panel p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[#E6007E]">info</span>
                    <div>
                        <p class="text-sm font-bold text-[#E6007E]">Recomendación de mapeo</p>
                        <p class="mt-1 text-sm leading-6 text-gray-600">Usa esta pantalla para validar sugerencias automáticas, no para mapear a ciegas. Si `name`, `price_tax_incl` o `quantity` quedan sin columna fiable, corrige aquí antes de persistir las filas del lote.</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Cuando termines, puedes dejar el lote guardado para revisión manual o usar `Guardar y procesar + normalizar` para lanzar de una vez importación completa, tags, revisión EAN y preparación de categorías en segundo plano.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@include('imports._activity_overlay')
@endsection

@push('scripts')
    <script>
        (() => {
            const overlay = document.getElementById('import-activity-overlay');
            const form = document.getElementById('import-mapping-form');
            const supplierName = @js($mappingSupplierName);
            const fileName = @js($mappingFileName);

            if (!overlay || !form) {
                return;
            }

            const titleEl = document.getElementById('import-activity-title');
            const contextEl = document.getElementById('import-activity-context');
            const subtitleEl = document.getElementById('import-activity-subtitle');
            const stageEl = document.getElementById('import-activity-stage');
            const percentEl = document.getElementById('import-activity-percent');
            const barEl = document.getElementById('import-activity-bar');
            const messageEl = document.getElementById('import-activity-message');
            const fileEl = document.getElementById('import-activity-file');
            const supplierEl = document.getElementById('import-activity-supplier');
            const actionEl = document.getElementById('import-activity-action');
            const noteEl = document.getElementById('import-activity-note');
            const errorEl = document.getElementById('import-activity-error');
            const buttons = Array.from(form.querySelectorAll('button[type="submit"]'));

            let submitAction = 'save';
            let progressTimer = null;
            let stageTimer = null;
            let virtualPercent = 6;

            const stageMessages = {
                save: [
                    'Validando columnas seleccionadas...',
                    'Leyendo archivo del proveedor...',
                    'Persistiendo filas del lote...',
                    'Cerrando guardado y preparando respuesta...',
                ],
                process: [
                    'Validando columnas seleccionadas...',
                    'Leyendo archivo del proveedor...',
                    'Persistiendo filas del lote...',
                    'Preparando procesamiento y normalización...',
                ],
            };

            const showOverlay = ({title, stage, message, action, indeterminate = false}) => {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                titleEl.textContent = title;
                contextEl.textContent = `${supplierName} · ${fileName}`;
                subtitleEl.textContent = 'La aplicación sigue trabajando en segundo plano de la petición actual.';
                stageEl.textContent = stage;
                percentEl.textContent = virtualPercent.toFixed(2);
                barEl.style.width = `${virtualPercent}%`;
                barEl.classList.toggle('mp-progress-indeterminate', indeterminate);
                messageEl.textContent = message;
                fileEl.textContent = fileName;
                supplierEl.textContent = supplierName;
                actionEl.textContent = action;
                noteEl.textContent = 'En XML o Excel grandes esta fase puede tardar mientras se leen filas y se guardan lotes en la base de datos.';
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    submitAction = button.value || 'save';
                });
            });

            form.addEventListener('submit', () => {
                buttons.forEach((button) => {
                    button.disabled = true;
                });

                virtualPercent = 6;
                const isProcess = submitAction === 'process';
                const messages = stageMessages[submitAction] || stageMessages.save;
                let stageIndex = 0;

                showOverlay({
                    title: isProcess ? 'Guardando y lanzando el proceso completo' : 'Guardando mapeo y filas del lote',
                    stage: messages[0],
                    message: isProcess
                        ? 'Se están validando columnas, guardando filas y preparando el pipeline automático.'
                        : 'Se están validando columnas y guardando las filas del archivo para la siguiente fase.',
                    action: isProcess ? 'Mapeo + proceso completo' : 'Mapeo + persistencia',
                    indeterminate: false,
                });

                if (progressTimer) {
                    clearInterval(progressTimer);
                }
                if (stageTimer) {
                    clearInterval(stageTimer);
                }

                progressTimer = window.setInterval(() => {
                    virtualPercent = Math.min(92, virtualPercent + (virtualPercent < 45 ? 7 : (virtualPercent < 75 ? 4 : 1.5)));
                    percentEl.textContent = virtualPercent.toFixed(2);
                    barEl.style.width = `${virtualPercent}%`;
                    if (virtualPercent >= 88) {
                        barEl.classList.add('mp-progress-indeterminate');
                    }
                }, 700);

                stageTimer = window.setInterval(() => {
                    stageIndex = Math.min(messages.length - 1, stageIndex + 1);
                    stageEl.textContent = messages[stageIndex];
                    messageEl.textContent = isProcess
                        ? 'El sistema sigue leyendo el archivo, guardando filas y preparando el lote completo.'
                        : 'El sistema sigue leyendo el archivo y guardando las filas del lote.';
                }, 1800);
            });

            document.querySelectorAll('[data-import-activity-link]').forEach((link) => {
                link.addEventListener('click', () => {
                    virtualPercent = 0;
                    showOverlay({
                        title: link.dataset.activityTitle || 'Abriendo pantalla',
                        stage: link.dataset.activityStage || 'Preparando vista...',
                        message: link.dataset.activityMessage || 'La aplicación sigue trabajando.',
                        action: 'Navegación interna',
                        indeterminate: true,
                    });
                });
            });
        })();
    </script>
@endpush
