@php
    $sectionRouteMap = [
        'proveedores' => 'projects.mpsfp.suppliers.*',
        'importaciones' => 'projects.mpsfp.imports.*',
        'normalizados' => 'projects.mpsfp.normalized.*',
        'cruce_proveedores' => 'projects.mpsfp.cross-suppliers.*',
        'categorias' => 'projects.mpsfp.categories.*',
        'maestros' => 'projects.mpsfp.master.*',
        'exportacion' => 'projects.mpsfp.export.*',
    ];

    $roleName = method_exists(auth()->user(), 'mpsfpPrimaryRole') ? auth()->user()->mpsfpPrimaryRole() : 'usuario';
    $isSuperadmin = $roleName === 'superadmin';
    $roleLabel = method_exists(auth()->user(), 'mpsfpPrimaryRole')
        ? \App\Support\MpsfpAccess::roleLabel($roleName)
        : 'Usuario';

    $activeSection = 'resumen';
    if (request()->routeIs('projects.mpsfp.section')) {
        $activeSection = request()->route('section') ?? ($section ?? 'resumen');
    } else {
        foreach ($sectionRouteMap as $sectionKey => $routePattern) {
            if (request()->routeIs($routePattern)) {
                $activeSection = $sectionKey;
                break;
            }
        }
    }

    $iconMap = [
        'resumen' => 'space_dashboard',
        'proveedores' => 'inventory_2',
        'importaciones' => 'upload_file',
        'normalizados' => 'rule',
        'cruce_proveedores' => 'compare_arrows',
        'maestros' => 'database',
        'categorias' => 'category',
        'ean' => 'qr_code_scanner',
        'duplicados' => 'content_copy',
        'exportacion' => 'ios_share',
    ];

    $primaryTabs = [
        'resumen' => [
            'label' => 'Resumen',
            'url' => route('projects.mpsfp.dashboard', $project),
            'enabled' => true,
            'description' => 'Portada operativa del submódulo.',
        ],
        'proveedores' => array_merge($sections['proveedores'] ?? [], ['url' => ($sections['proveedores']['url'] ?? route('projects.mpsfp.suppliers.index', $project))]),
        'importaciones' => array_merge($sections['importaciones'] ?? [], ['url' => ($sections['importaciones']['url'] ?? route('projects.mpsfp.imports.index', $project))]),
        'normalizados' => array_merge($sections['normalizados'] ?? [], ['url' => ($sections['normalizados']['url'] ?? route('projects.mpsfp.normalized.index', $project))]),
        'cruce_proveedores' => array_merge($sections['cruce_proveedores'] ?? [], ['url' => ($sections['cruce_proveedores']['url'] ?? route('projects.mpsfp.cross-suppliers.index', $project))]),
        'maestros' => array_merge($sections['maestros'] ?? [], ['url' => ($sections['maestros']['url'] ?? route('projects.mpsfp.master.index', $project))]),
        'categorias' => array_merge($sections['categorias'] ?? [], ['url' => ($sections['categorias']['url'] ?? route('projects.mpsfp.categories.review', $project))]),
        'ean' => array_merge($sections['ean'] ?? [], ['url' => ($sections['ean']['url'] ?? route('projects.mpsfp.section', ['project' => $project, 'section' => 'ean']))]),
        'duplicados' => array_merge($sections['duplicados'] ?? [], ['url' => ($sections['duplicados']['url'] ?? route('projects.mpsfp.section', ['project' => $project, 'section' => 'duplicados']))]),
        'exportacion' => array_merge($sections['exportacion'] ?? [], ['url' => ($sections['exportacion']['url'] ?? route('projects.mpsfp.export.index', $project))]),
    ];

    $subnav = [
        'resumen' => [
            ['label' => 'Nuevo proveedor', 'url' => route('projects.mpsfp.suppliers.create', $project), 'enabled' => (bool) data_get($sections, 'proveedores.actions.create', false), 'icon' => 'inventory_2'],
            ['label' => 'Nueva importación', 'url' => route('projects.mpsfp.imports.create', $project), 'enabled' => (bool) data_get($sections, 'importaciones.actions.create', false), 'icon' => 'upload_file'],
            ['label' => 'Ver normalizados', 'url' => route('projects.mpsfp.normalized.index', $project), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'icon' => 'rule'],
            ['label' => 'Ver maestros', 'url' => route('projects.mpsfp.master.index', $project), 'enabled' => (bool) data_get($sections, 'maestros.view', false), 'icon' => 'database'],
        ],
        'proveedores' => [
            ['label' => 'Todos', 'url' => route('projects.mpsfp.suppliers.index', $project), 'enabled' => (bool) data_get($sections, 'proveedores.view', false), 'is_active' => ! request()->filled('is_active') && ! request()->filled('search'), 'icon' => 'grid_view'],
            ['label' => 'Activos', 'url' => route('projects.mpsfp.suppliers.index', [$project, 'is_active' => 1]), 'enabled' => (bool) data_get($sections, 'proveedores.view', false), 'is_active' => request('is_active') === '1', 'icon' => 'check_circle'],
            ['label' => 'Inactivos', 'url' => route('projects.mpsfp.suppliers.index', [$project, 'is_active' => 0]), 'enabled' => (bool) data_get($sections, 'proveedores.view', false), 'is_active' => request('is_active') === '0', 'icon' => 'pause_circle'],
            ['label' => 'Alta nueva', 'url' => route('projects.mpsfp.suppliers.create', $project), 'enabled' => (bool) data_get($sections, 'proveedores.actions.create', false), 'is_active' => request()->routeIs('projects.mpsfp.suppliers.create'), 'icon' => 'add_circle'],
        ],
        'importaciones' => [
            ['label' => 'Todas', 'url' => route('projects.mpsfp.imports.index', $project), 'enabled' => (bool) data_get($sections, 'importaciones.view', false), 'is_active' => ! request()->filled('status') && ! request()->filled('supplier_id'), 'icon' => 'table_rows'],
            ['label' => 'Procesadas', 'url' => route('projects.mpsfp.imports.index', [$project, 'status' => 'processed']), 'enabled' => (bool) data_get($sections, 'importaciones.view', false), 'is_active' => request('status') === 'processed', 'icon' => 'task_alt'],
            ['label' => 'Fallidas', 'url' => route('projects.mpsfp.imports.index', [$project, 'status' => 'failed']), 'enabled' => (bool) data_get($sections, 'importaciones.view', false), 'is_active' => request('status') === 'failed', 'icon' => 'error'],
            ['label' => 'Subir archivo', 'url' => route('projects.mpsfp.imports.create', $project), 'enabled' => (bool) data_get($sections, 'importaciones.actions.create', false), 'is_active' => request()->routeIs('projects.mpsfp.imports.create'), 'icon' => 'upload_file'],
        ],
        'normalizados' => [
            ['label' => 'Todos', 'url' => route('projects.mpsfp.normalized.index', $project), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => ! request()->filled('master_link') && ! request()->filled('barcode_status'), 'icon' => 'grid_view'],
            ['label' => 'Sin maestro', 'url' => route('projects.mpsfp.normalized.index', [$project, 'master_link' => 'without_master']), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('master_link') === 'without_master', 'icon' => 'database_off'],
            ['label' => 'Con maestro', 'url' => route('projects.mpsfp.normalized.index', [$project, 'master_link' => 'with_master']), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('master_link') === 'with_master', 'icon' => 'database'],
            ['label' => 'Sin EAN', 'url' => route('projects.mpsfp.normalized.index', [$project, 'barcode_status' => 'missing']), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('barcode_status') === 'missing', 'icon' => 'qr_code_2'],
            ['label' => 'Cruce proveedores', 'url' => route('projects.mpsfp.cross-suppliers.index', $project), 'enabled' => (bool) data_get($sections, 'cruce_proveedores.view', false), 'is_active' => request()->routeIs('projects.mpsfp.cross-suppliers.*'), 'icon' => 'compare_arrows'],
            ['label' => 'Revisar categorías', 'url' => route('projects.mpsfp.categories.review', $project), 'enabled' => (bool) data_get($sections, 'categorias.view', false), 'is_active' => request()->routeIs('projects.mpsfp.categories.*'), 'icon' => 'category'],
            ['label' => '100 por página', 'url' => route('projects.mpsfp.normalized.index', array_merge(['project' => $project], request()->except('page', 'per_page'), ['per_page' => 100])), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('per_page') === '100', 'icon' => 'filter_9_plus'],
            ['label' => '500 por página', 'url' => route('projects.mpsfp.normalized.index', array_merge(['project' => $project], request()->except('page', 'per_page'), ['per_page' => 500])), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('per_page') === '500', 'icon' => 'dataset'],
            ['label' => 'Todos', 'url' => route('projects.mpsfp.normalized.index', array_merge(['project' => $project], request()->except('page', 'per_page'), ['per_page' => 'all'])), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'is_active' => request('per_page') === 'all', 'icon' => 'select_all'],
        ],
        'cruce_proveedores' => [
            ['label' => 'Todos los grupos', 'url' => route('projects.mpsfp.cross-suppliers.index', $project), 'enabled' => (bool) data_get($sections, 'cruce_proveedores.view', false), 'is_active' => ! request()->filled('supplier_id') && ! request()->filled('search'), 'icon' => 'account_tree'],
            ['label' => 'Volver a normalizados', 'url' => route('projects.mpsfp.normalized.index', $project), 'enabled' => (bool) data_get($sections, 'normalizados.view', false), 'icon' => 'rule'],
        ],
        'maestros' => [
            ['label' => 'Todos', 'url' => route('projects.mpsfp.master.index', $project), 'enabled' => (bool) data_get($sections, 'maestros.view', false), 'is_active' => ! request()->filled('is_approved'), 'icon' => 'table_rows'],
            ['label' => 'Aprobados', 'url' => route('projects.mpsfp.master.index', [$project, 'is_approved' => 1]), 'enabled' => (bool) data_get($sections, 'maestros.view', false), 'is_active' => request('is_approved') === '1', 'icon' => 'task_alt'],
            ['label' => 'Pendientes', 'url' => route('projects.mpsfp.master.index', [$project, 'is_approved' => 0]), 'enabled' => (bool) data_get($sections, 'maestros.view', false), 'is_active' => request('is_approved') === '0', 'icon' => 'pending_actions'],
            ['label' => 'Exportación', 'url' => route('projects.mpsfp.export.index', $project), 'enabled' => (bool) data_get($sections, 'exportacion.view', false), 'is_active' => request()->routeIs('projects.mpsfp.export.*'), 'icon' => 'ios_share'],
        ],
        'categorias' => [
            ['label' => 'Sugerencias', 'url' => route('projects.mpsfp.categories.review', $project), 'enabled' => (bool) data_get($sections, 'categorias.view', false), 'is_active' => request()->routeIs('projects.mpsfp.categories.*'), 'icon' => 'auto_awesome'],
            ['label' => 'Árbol maestro', 'url' => route('categories.tree_es'), 'enabled' => (bool) data_get($sections, 'categorias.view', false), 'icon' => 'account_tree'],
        ],
        'ean' => [
            ['label' => 'Todas las incidencias', 'url' => route('ean-issues.index'), 'enabled' => (bool) data_get($sections, 'ean.view', false), 'icon' => 'qr_code_scanner'],
        ],
        'duplicados' => [
            ['label' => 'Todos los grupos', 'url' => route('duplicates.index'), 'enabled' => (bool) data_get($sections, 'duplicados.view', false), 'icon' => 'content_copy'],
        ],
        'exportacion' => [
            ['label' => 'Pantalla de exportación', 'url' => route('projects.mpsfp.export.index', $project), 'enabled' => (bool) data_get($sections, 'exportacion.view', false), 'is_active' => request()->routeIs('projects.mpsfp.export.*'), 'icon' => 'ios_share'],
            ['label' => 'Maestros aprobados', 'url' => route('projects.mpsfp.master.index', [$project, 'is_approved' => 1]), 'enabled' => (bool) data_get($sections, 'maestros.view', false), 'is_active' => request()->routeIs('projects.mpsfp.master.*') && request('is_approved') === '1', 'icon' => 'verified'],
        ],
    ];

    $activeDescription = $activeSection === 'resumen'
        ? 'Portada operativa del módulo.'
        : ($primaryTabs[$activeSection]['description'] ?? 'Sección del submódulo.');

    $activeSubnav = $subnav[$activeSection] ?? [];
@endphp

<div class="mpsfp-shell p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.28em] text-gray-400">Submódulo de proyecto</p>
            <div class="mt-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mpsfp-pill status-pink">{{ $roleLabel }}</span>
                    @if ($isSuperadmin)
                        <span class="mpsfp-pill status-green">Acceso total</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500">Navega por secciones del módulo con tabs y usa el submenú contextual de la sección activa.</p>
            </div>
        </div>

        <div class="action-stack">
            <a href="{{ route('projects.show', $project) }}" class="btn-secondary">Proyecto</a>
            <a href="{{ route('projects.mpsfp.dashboard', $project) }}" class="btn-primary">Ir al resumen</a>
        </div>
    </div>

    <div class="mt-5 overflow-x-auto">
        <div class="flex min-w-max gap-2 pb-1 md:min-w-0 md:flex-wrap">
            @foreach ($primaryTabs as $sectionKey => $tab)
                @php
                    $isEnabled = $sectionKey === 'resumen' ? true : ($isSuperadmin ? true : (bool) ($tab['enabled'] ?? false));
                    $isActive = $activeSection === $sectionKey;
                @endphp
                @if ($isEnabled)
                    <a href="{{ $tab['url'] }}"
                       class="mpsfp-tab {{ $isActive ? 'bg-[#E6007E] text-white shadow-md shadow-[#E6007E]/20' : 'bg-white text-[#555555] hover:bg-[#eae8e7] hover:text-[#E6007E]' }}">
                        <span class="material-symbols-outlined" @if($isActive) style="font-variation-settings: 'FILL' 1, 'wght' 500;" @endif>{{ $iconMap[$sectionKey] ?? 'dashboard' }}</span>
                        <span>{{ $tab['label'] ?? ucfirst($sectionKey) }}</span>
                    </a>
                @else
                    <span class="btn-disabled">{{ $tab['label'] ?? ucfirst($sectionKey) }}</span>
                @endif
            @endforeach
        </div>
    </div>

    <div class="mt-5 mpsfp-soft-panel p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.28em] text-gray-400">Sección activa</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <p class="font-headline text-lg font-extrabold tracking-tight text-mptext">{{ $primaryTabs[$activeSection]['label'] ?? ucfirst($activeSection) }}</p>
                    <span class="mpsfp-pill status-pink">Estás en {{ $primaryTabs[$activeSection]['label'] ?? ucfirst($activeSection) }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">{{ $activeDescription }}</p>
            </div>
        </div>

        @if (! empty($activeSubnav))
            <div class="mt-4 overflow-x-auto">
                <div class="flex min-w-max gap-2 md:min-w-0 md:flex-wrap">
                    @foreach ($activeSubnav as $item)
                        @php
                            $isEnabled = $isSuperadmin ? true : ! empty($item['enabled']);
                            $isActiveSubitem = (bool) ($item['is_active'] ?? false);
                        @endphp
                        @if ($isEnabled)
                            <a href="{{ $item['url'] }}"
                               class="mpsfp-subtab {{ $isActiveSubitem ? 'bg-white text-[#E6007E] shadow-sm' : 'bg-white text-[#555555] hover:bg-[#f5e8ef] hover:text-[#E6007E]' }}">
                                @if (! empty($item['icon']))
                                    <span class="material-symbols-outlined text-base">{{ $item['icon'] }}</span>
                                @endif
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @else
                            <span class="btn-disabled">{{ $item['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
