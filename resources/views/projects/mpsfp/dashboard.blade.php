@extends('layouts.app')

@section('title', 'MPSFP')
@section('page_title', 'MPSFP')

@section('content')
@php
    $supplierAccess = $access['sections']['proveedores'] ?? [];
    $importAccess = $access['sections']['importaciones'] ?? [];
    $normalizedAccess = $access['sections']['normalizados'] ?? [];
    $masterAccess = $access['sections']['maestros'] ?? [];
    $eanAccess = $access['sections']['ean'] ?? [];
    $duplicateAccess = $access['sections']['duplicados'] ?? [];
    $crossSupplierAccess = $access['sections']['cruce_proveedores'] ?? [];
    $categoryAccess = $access['sections']['categorias'] ?? [];
    $exportAccess = $access['sections']['exportacion'] ?? [];

    $quickActions = collect([
        [
            'label' => 'Proveedores',
            'url' => route('projects.mpsfp.suppliers.index', $project),
            'enabled' => ! empty($supplierAccess['view']),
            'icon' => 'inventory_2',
        ],
        [
            'label' => 'Importaciones',
            'url' => route('projects.mpsfp.imports.index', $project),
            'enabled' => ! empty($importAccess['view']),
            'icon' => 'upload_file',
        ],
        [
            'label' => 'Normalizados',
            'url' => route('projects.mpsfp.normalized.index', $project),
            'enabled' => ! empty($normalizedAccess['view']),
            'icon' => 'rule',
        ],
        [
            'label' => 'Categorías',
            'url' => route('projects.mpsfp.categories.review', $project),
            'enabled' => ! empty($categoryAccess['view']),
            'icon' => 'category',
        ],
        [
            'label' => 'Cruce',
            'url' => route('projects.mpsfp.cross-suppliers.index', $project),
            'enabled' => ! empty($crossSupplierAccess['view']),
            'icon' => 'compare_arrows',
        ],
        [
            'label' => 'Maestros',
            'url' => route('projects.mpsfp.master.index', $project),
            'enabled' => ! empty($masterAccess['view']),
            'icon' => 'database',
        ],
        [
            'label' => 'Exportación',
            'url' => route('projects.mpsfp.export.index', $project),
            'enabled' => ! empty($exportAccess['view']),
            'icon' => 'ios_share',
        ],
    ])->filter(fn ($item) => $item['enabled'])->values();

    $taskCards = collect([
        [
            'title' => 'Resolver incidencias EAN',
            'description' => $context['openEanIssues'] > 0
                ? 'Hay productos con EAN vacío, inválido o pendiente de revisión.'
                : 'No hay incidencias EAN abiertas ahora mismo.',
            'cta' => 'Ver detalle',
            'url' => ! empty($eanAccess['view']) ? route('projects.mpsfp.section', ['project' => $project, 'section' => 'ean']) : null,
            'icon' => 'priority_high',
            'icon_color' => $context['openEanIssues'] > 0 ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700',
            'button_class' => 'btn-secondary',
            'value' => $context['openEanIssues'],
        ],
        [
            'title' => 'Revisar categorías sugeridas',
            'description' => $context['suggestedCategoriesCount'] > 0
                ? 'Hay productos con categoría sugerida pendientes de confirmación final en la app.'
                : 'No hay sugerencias de categoría pendientes en este momento.',
            'cta' => 'Revisar',
            'url' => ! empty($categoryAccess['view']) ? route('projects.mpsfp.categories.review', $project) : null,
            'icon' => 'auto_fix_high',
            'icon_color' => 'bg-[#ffd9e2] text-[#8e004b]',
            'button_class' => 'btn-secondary',
            'value' => $context['suggestedCategoriesCount'],
        ],
        [
            'title' => 'Validar consolidación a maestro',
            'description' => $context['unlinkedNormalizedProducts'] > 0
                ? 'Quedan productos normalizados sin consolidar todavía en catálogo maestro.'
                : 'No quedan productos pendientes de consolidación a maestro.',
            'cta' => 'Validar',
            'url' => ! empty($masterAccess['view']) ? route('projects.mpsfp.master.index', $project) : null,
            'icon' => 'check_circle',
            'icon_color' => 'bg-[#88fc7d] text-[#00530a]',
            'button_class' => 'btn-secondary',
            'value' => $context['unlinkedNormalizedProducts'],
        ],
    ])->filter(fn ($task) => $task['url'] !== null)->values();

    $criticalTasksCount = $taskCards->where('value', '>', 0)->count();
    $systemStatus = ($context['openEanIssues'] + $context['pendingDuplicateGroups']) > 0 ? 'Atención requerida' : 'Óptimo';
@endphp

<div class="space-y-8">
    @include('projects.mpsfp._nav', ['project' => $project, 'sections' => $sections])

    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-secondary">
                        <span>Dashboard</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span>Proyectos</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span class="font-bold text-primary">MPSFP</span>
                    </div>
                    <div class="mt-3">
                        <h2 class="font-headline text-3xl font-extrabold tracking-tight text-[#1b1c1c]">Panel operativo del catálogo</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-secondary">Centro operativo de Musical Princesa para proveedores, importaciones, productos normalizados, categorías sugeridas, cruce entre proveedores, catálogo maestro y exportación.</p>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-3 xl:w-auto xl:min-w-[28rem]">
                    <div class="relative w-full">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-lg">search</span>
                        <input type="text" placeholder="Buscar proveedor, producto, EAN o importación..." class="w-full rounded-full border-none bg-surface-container-low py-2.5 pl-10 pr-4 text-sm focus:bg-white focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="flex flex-wrap items-center justify-start gap-3 xl:justify-end">
                        <span class="mpsfp-pill {{ $systemStatus === 'Óptimo' ? 'status-green' : 'status-amber' }}">Estado de sistema: {{ $systemStatus }}</span>
                    </div>
                </div>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Proveedores activos</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($context['activeSuppliers']) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">groups</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Importaciones recientes</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($context['processedImports']) }} <span class="text-xs font-medium text-tertiary">/{{ $context['importsToday'] }} hoy</span></h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">cloud_upload</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Normalizados pendientes</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($context['unlinkedNormalizedProducts']) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">pending_actions</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Categorías sugeridas</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-primary">{{ number_format($context['suggestedCategoriesCount']) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">auto_awesome</span>
                    </div>
                </div>
                <div class="mpsfp-kpi bg-error-container/10">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-on-error-container">Incidencias EAN</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-error">{{ number_format($context['openEanIssues']) }}</h3>
                        <span class="material-symbols-outlined text-error opacity-40">warning</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Sin imagen</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($context['productsWithoutImage']) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">image_not_supported</span>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.75rem] bg-[#efeded] px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
                <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
                    <h2 class="flex items-center gap-2 font-headline text-xl font-bold">
                        <span class="h-6 w-2 rounded-full bg-primary"></span>
                        Flujo operativo de datos
                    </h2>
                    <span class="text-xs font-bold uppercase tracking-tight text-secondary">Estado de sistema: {{ $systemStatus }}</span>
                </div>
                <div class="relative overflow-x-auto px-2">
                    <div class="absolute left-3 right-3 top-6 hidden h-[2px] bg-[#d9d4d4] lg:block"></div>
                    <div class="absolute left-3 top-6 hidden h-[2px] w-[41.5%] bg-[#ec9bc1] lg:block"></div>
                    <div class="flex min-w-max items-start justify-between gap-7">
                        @php
                            $activeSuppliers = (int) ($context['activeSuppliers'] ?? 0);
                            $totalImports = (int) ($context['totalImports'] ?? 0);
                            $draftImports = (int) ($context['draftImports'] ?? 0);
                            $activeMappings = (int) ($context['activeMappings'] ?? 0);
                            $normalizedProducts = (int) ($context['normalizedProducts'] ?? 0);
                            $unlinkedNormalizedProducts = (int) ($context['unlinkedNormalizedProducts'] ?? 0);

                            // Paso actual según datos existentes.
                            // Nota: con el borrado de proveedores/importaciones, estos contadores quedan en 0
                            // y el flujo debe reflejar que no hay dónde empezar aún.
                            if ($activeSuppliers <= 0) {
                                $currentFlowStep = 1; // Proveedor
                            } elseif ($totalImports <= 0) {
                                $currentFlowStep = 2; // Importación
                            } elseif ($draftImports <= 0) {
                                $currentFlowStep = 3; // Preview
                            } elseif ($activeMappings <= 0) {
                                $currentFlowStep = 4; // Mapeo
                            } elseif ($normalizedProducts <= 0) {
                                $currentFlowStep = 5; // Proceso
                            } elseif ($unlinkedNormalizedProducts > 0) {
                                $currentFlowStep = 6; // Normalizados
                            } else {
                                // Para el resto de pasos aún podemos "dar" el flujo cuando hay datos suficientes.
                                $currentFlowStep = 7;
                            }
                        @endphp

                        @foreach ([
                            ['step' => 1, 'label' => 'Proveedor'],
                            ['step' => 2, 'label' => 'Importación'],
                            ['step' => 3, 'label' => 'Preview'],
                            ['step' => 4, 'label' => 'Mapeo'],
                            ['step' => 5, 'label' => 'Proceso'],
                            ['step' => 6, 'label' => 'Normalizados'],
                            ['step' => 7, 'label' => 'Categorías'],
                            ['step' => 8, 'label' => 'Maestro'],
                            ['step' => 9, 'label' => 'Exportación'],
                        ] as $flowStep)
                            @php
                                $state = $flowStep['step'] < $currentFlowStep ? 'done' : ($flowStep['step'] === $currentFlowStep ? 'current' : 'pending');
                            @endphp
                            <div class="group mpsfp-step min-w-[6.1rem] cursor-default gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-[1.05rem] font-extrabold transition-all duration-200 group-hover:bg-[#e6007e] group-hover:text-white group-hover:shadow-[0_12px_24px_-12px_rgba(195,0,115,0.55)] {{ $state === 'done' ? 'bg-[#c30073] text-white shadow-[0_12px_24px_-12px_rgba(195,0,115,0.55)]' : ($state === 'current' ? 'border-2 border-white bg-[#ffbfd8] text-[#3e001e] shadow-[0_10px_22px_-14px_rgba(195,0,115,0.45)]' : 'border-2 border-[#ddd8d8] bg-white text-[#646464] shadow-[0_10px_18px_-16px_rgba(27,28,28,0.25)]') }}">
                                    {{ $flowStep['step'] }}
                                </div>
                                <span class="text-center text-[0.66rem] font-extrabold uppercase tracking-[0.02em] transition-colors duration-200 group-hover:text-primary {{ $state === 'current' ? 'text-primary' : ($state === 'pending' ? 'text-secondary/55' : 'text-secondary') }}">{{ $flowStep['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <section class="mpsfp-panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-container bg-primary/5 px-6 py-5">
                    <h2 class="font-headline text-lg font-bold text-[#1b1c1c]">Qué hacer ahora</h2>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $criticalTasksCount }} tareas críticas</span>
                </div>
                <div class="p-2">
                    @foreach ($taskCards as $task)
                        <div class="group flex flex-col gap-4 rounded-xl p-4 transition-colors hover:bg-surface-container-low sm:flex-row sm:items-center">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $task['icon_color'] }}">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">{{ $task['icon'] }}</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold">{{ $task['title'] }}</h4>
                                <p class="text-xs text-secondary">{{ $task['description'] }}</p>
                            </div>
                            <a href="{{ $task['url'] }}" class="w-full rounded-lg bg-surface-container-high px-4 py-2 text-center text-xs font-bold text-on-surface transition-all group-hover:bg-primary group-hover:text-white sm:w-auto">
                                {{ $task['cta'] }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mpsfp-panel p-6">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-headline text-lg font-bold">Alertas e incidencias</h2>
                    @can('logs.view')
                        <a href="{{ route('activity-logs.index') }}" class="text-xs font-bold text-primary hover:underline">Ver todo el historial</a>
                    @endcan
                </div>
                <div class="space-y-4">
                    <div class="flex items-start gap-4 rounded-xl border-l-4 {{ $context['openEanIssues'] > 0 ? 'border-error bg-error-container/5' : 'border-blue-500 bg-blue-50/60' }} p-4">
                        <span class="material-symbols-outlined mt-0.5 {{ $context['openEanIssues'] > 0 ? 'text-error' : 'text-blue-600' }}">{{ $context['openEanIssues'] > 0 ? 'error' : 'info' }}</span>
                        <div>
                            <p class="text-sm font-bold text-[#1b1c1c]">{{ $context['openEanIssues'] > 0 ? 'Incidencias EAN pendientes de revisión' : 'No hay incidencias EAN abiertas' }}</p>
                            <p class="mt-1 text-xs text-secondary">{{ $context['openEanIssues'] > 0 ? 'Se han detectado productos con EAN vacío, inválido o conflictivo dentro del catálogo actual.' : 'El catálogo actual no presenta incidencias EAN abiertas.' }}</p>
                            <span class="mt-2 inline-block text-[10px] font-bold uppercase {{ $context['openEanIssues'] > 0 ? 'text-error' : 'text-blue-600' }}">{{ number_format($context['openEanIssues']) }} abiertos</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 rounded-xl border-l-4 {{ $context['pendingDuplicateGroups'] > 0 ? 'border-amber-500 bg-surface-container-low' : 'border-green-500 bg-green-50/60' }} p-4">
                        <span class="material-symbols-outlined mt-0.5 {{ $context['pendingDuplicateGroups'] > 0 ? 'text-amber-500' : 'text-green-600' }}">{{ $context['pendingDuplicateGroups'] > 0 ? 'warning' : 'check_circle' }}</span>
                        <div>
                            <p class="text-sm font-bold text-[#1b1c1c]">{{ $context['pendingDuplicateGroups'] > 0 ? 'Productos repetidos entre proveedores' : 'No hay duplicados pendientes' }}</p>
                            <p class="mt-1 text-xs text-secondary">{{ $context['pendingDuplicateGroups'] > 0 ? 'Existen grupos pendientes de comparación o unificación por EAN compartido entre proveedores.' : 'El cruce de proveedores no presenta grupos pendientes ahora mismo.' }}</p>
                            <span class="mt-2 inline-block text-[10px] font-bold uppercase {{ $context['pendingDuplicateGroups'] > 0 ? 'text-amber-600' : 'text-green-700' }}">{{ number_format($context['pendingDuplicateGroups']) }} grupos</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-8">
            <section>
                <h2 class="mb-4 font-headline text-lg font-bold">Accesos rápidos</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="group flex flex-col items-center gap-2 rounded-xl bg-surface-container-lowest p-4 transition-all hover:bg-[#e6007e] hover:text-white hover:shadow-lg hover:shadow-pink-200/60">
                            <span class="material-symbols-outlined text-primary transition-colors group-hover:text-white">{{ $action['icon'] }}</span>
                            <span class="text-center text-[11px] font-bold">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="mpsfp-panel p-6">
                <h2 class="mb-6 font-headline text-lg font-bold">Actividad reciente</h2>
                <div class="relative space-y-6 before:absolute before:bottom-2 before:left-[11px] before:top-2 before:w-0.5 before:bg-surface-container">
                    @forelse ($context['recentUserLogs'] as $log)
                        <div class="relative flex flex-col gap-1 pl-8">
                            <div class="absolute left-0 top-1 z-10 flex h-6 w-6 items-center justify-center rounded-full border-2 border-primary-container bg-surface-container-lowest">
                                <div class="h-2 w-2 rounded-full bg-primary"></div>
                            </div>
                            <p class="text-xs font-bold">{{ $log->action }}</p>
                            <p class="text-[11px] text-secondary">{{ \Illuminate\Support\Str::limit($log->description, 90) }}</p>
                            <span class="text-[10px] text-secondary/60">{{ $log->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                        </div>
                    @empty
                        <div class="rounded-xl bg-surface-container-low p-4 text-sm text-secondary">
                            No hay actividad reciente registrada para este usuario.
                        </div>
                    @endforelse
                </div>
                @can('logs.view')
                    <a href="{{ route('activity-logs.index', ['user_id' => auth()->id()]) }}" class="mt-8 block w-full rounded-lg border border-surface-container-high py-2 text-center text-xs font-bold text-secondary transition-all hover:bg-surface-container-low">Ver log completo</a>
                @endcan
            </section>
        </div>
    </div>
</div>
@endsection
