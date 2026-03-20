@extends('layouts.app')

@section('title', 'Productos normalizados')
@section('page_title', 'Productos normalizados')

@section('content')
@php
    $allSuppliers = collect($suppliers ?? []);
    $stats = $normalizedStats ?? [
        'total' => $products->total(),
        'pending_review' => 0,
        'errors' => 0,
        'suggested_categories' => 0,
    ];
    $pageSizes = $pageSizeOptions ?? [50, 100, 200, 500, 1000, 5000, 20000, 'all'];
    $selectedSupplier = request('supplier_id') ? $allSuppliers->firstWhere('id', (int) request('supplier_id')) : null;
    $selectedPerPage = (string) request('per_page', (string) $products->perPage());
    $perPageLabel = $selectedPerPage === 'all' ? 'Todos' : number_format($products->perPage());
    $filterAction = isset($mpsfpProject)
        ? route('projects.mpsfp.normalized.index', $mpsfpProject)
        : route('products.normalized.index');
    $clearUrl = isset($mpsfpProject)
        ? route('projects.mpsfp.normalized.index', $mpsfpProject)
        : route('products.normalized.index');
    $activeFilters = array_filter([
        request('search') ? 'Búsqueda: ' . request('search') : null,
        request('supplier_import_id') ? 'Importación: #' . request('supplier_import_id') : null,
        request('catalog_year') ? 'Año catálogo: ' . request('catalog_year') : null,
        request('imported_from') ? 'Desde: ' . request('imported_from') : null,
        request('imported_to') ? 'Hasta: ' . request('imported_to') : null,
        $selectedSupplier ? 'Proveedor: ' . $selectedSupplier->name : null,
        request('validation_status') ? 'Validación: ' . request('validation_status') : null,
        request('barcode_status') ? 'Código: ' . request('barcode_status') : null,
        request('price_issue') === 'missing_cost' ? 'Sin precio compra' : null,
        request('price_issue') === 'missing_sale' ? 'Sin precio venta' : null,
        request('price_issue') === 'missing_any' ? 'Sin algún precio' : null,
        request('price_issue') === 'sale_below_cost' ? 'Venta pública menor que proveedor' : null,
        request('master_link') === 'with_master' ? 'Con maestro' : null,
        request('master_link') === 'without_master' ? 'Sin maestro' : null,
        request('per_page') ? 'Por página: ' . ($selectedPerPage === 'all' ? 'Todos' : request('per_page')) : null,
    ]);
@endphp

<div class="space-y-8">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
    @endif

    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <nav class="flex items-center gap-2 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-400">
                        <span>Dashboard</span>
                        <span class="material-symbols-outlined text-xs">chevron_right</span>
                        <span>Proyectos</span>
                        <span class="material-symbols-outlined text-xs">chevron_right</span>
                        @if (isset($mpsfpProject))
                            <span>MPSFP</span>
                            <span class="material-symbols-outlined text-xs">chevron_right</span>
                        @endif
                        <span class="text-primary">Normalizados</span>
                    </nav>
                    <h1 class="mt-3 font-headline text-3xl font-extrabold tracking-tight text-[#1b1c1c]">Productos normalizados</h1>
                    <p class="mt-2 max-w-4xl text-sm leading-6 text-secondary">Vista operativa del catálogo intermedio antes de consolidar a maestro. Aquí revisas identidad, origen, categoría sugerida, precios y estado técnico con una lectura mucho más limpia.</p>
                </div>

                <div class="action-stack">
                    @if (isset($mpsfpProject))
                        <a href="{{ route('projects.mpsfp.categories.review', $mpsfpProject) }}" class="btn-secondary">Revisar categorías</a>
                        <a href="{{ route('projects.mpsfp.export.index', $mpsfpProject) }}" class="btn-primary">Ir a exportación</a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Total procesados</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-bold text-on-surface">{{ number_format($stats['total']) }}</h3>
                        <span class="flex items-center text-tertiary text-xs font-bold mb-1">
                            <span class="material-symbols-outlined mr-0.5 text-sm">inventory_2</span>
                            catálogo
                        </span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Pendientes revisión</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-bold text-on-surface">{{ number_format($stats['pending_review']) }}</h3>
                        <span class="rounded bg-secondary-container px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-tighter text-on-secondary-fixed">Revisión</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Errores de validación</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-bold text-error">{{ number_format($stats['errors']) }}</h3>
                        <span class="material-symbols-outlined rounded-full bg-error p-1 text-xs text-error-container">priority_high</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Sugerencias de categoría</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-bold text-on-surface">{{ number_format($stats['suggested_categories']) }}</h3>
                        <div class="flex -space-x-2">
                            <div class="h-6 w-6 rounded-full border-2 border-white bg-pink-100"></div>
                            <div class="h-6 w-6 rounded-full border-2 border-white bg-pink-200"></div>
                            <div class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-pink-300 text-[0.5rem] font-bold">IA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mpsfp-panel overflow-hidden">
        <div class="border-b border-zinc-50 bg-zinc-50/30 p-6">
            <div class="compact-toolbar">
                <div>
                    <h2 class="font-headline text-lg font-bold">Filtros y búsqueda</h2>
                    <p class="mt-1 text-sm text-secondary">Afina por proveedor, validación, código y enlace a catálogo maestro.</p>
                </div>
                <div class="self-start rounded-full bg-surface-container-low px-4 py-2 text-sm font-semibold text-secondary md:self-auto">
                    Mostrando {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} de {{ number_format($products->total()) }}
                </div>
            </div>

            <form method="GET" action="{{ $filterAction }}" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                @if (request('supplier_import_id'))
                    <input type="hidden" name="supplier_import_id" value="{{ request('supplier_import_id') }}">
                @endif
                @if (request('price_issue'))
                    <input type="hidden" name="price_issue" value="{{ request('price_issue') }}">
                @endif
                <div class="relative min-w-0 sm:col-span-2 xl:col-span-2">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-lg">search</span>
                    <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nombre, EAN o referencia..." class="w-full rounded-xl border-none bg-white py-2.5 pl-10 pr-4 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                </div>
                <div class="relative">
                    <select id="supplier_id" name="supplier_id" class="w-full appearance-none rounded-xl border-none bg-white py-2.5 pl-4 pr-10 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                        <option value="">Proveedor: Todos</option>
                        @foreach ($allSuppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}{{ $supplier->is_active ? '' : ' (Inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-zinc-400 text-lg">expand_more</span>
                </div>
                <div class="relative">
                    <input id="catalog_year" type="number" name="catalog_year" min="2020" max="{{ now()->year + 2 }}" value="{{ request('catalog_year') }}" placeholder="Año catálogo" class="w-full rounded-xl border-none bg-white py-2.5 px-4 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                </div>
                <div class="relative">
                    <input id="imported_from" type="date" name="imported_from" value="{{ request('imported_from') }}" class="w-full rounded-xl border-none bg-white py-2.5 px-4 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                </div>
                <div class="relative">
                    <input id="imported_to" type="date" name="imported_to" value="{{ request('imported_to') }}" class="w-full rounded-xl border-none bg-white py-2.5 px-4 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                </div>
                <div class="relative">
                    <select id="validation_status" name="validation_status" class="w-full appearance-none rounded-xl border-none bg-white py-2.5 pl-4 pr-10 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                        <option value="">Estado: Todos</option>
                        <option value="ok" {{ request('validation_status') === 'ok' ? 'selected' : '' }}>Validado</option>
                        <option value="warning" {{ request('validation_status') === 'warning' ? 'selected' : '' }}>Aviso</option>
                        <option value="error" {{ request('validation_status') === 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-zinc-400 text-lg">expand_more</span>
                </div>
                <div class="relative">
                    <select id="barcode_status" name="barcode_status" class="w-full appearance-none rounded-xl border-none bg-white py-2.5 pl-4 pr-10 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                        <option value="">Código: Todos</option>
                        <option value="ok" {{ request('barcode_status') === 'ok' ? 'selected' : '' }}>Correcto</option>
                        <option value="non_ean" {{ request('barcode_status') === 'non_ean' ? 'selected' : '' }}>UPC / GTIN / código interno</option>
                        <option value="invalid_ean" {{ request('barcode_status') === 'invalid_ean' ? 'selected' : '' }}>EAN inválido</option>
                        <option value="missing" {{ request('barcode_status') === 'missing' ? 'selected' : '' }}>Sin código</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-zinc-400 text-lg">expand_more</span>
                </div>
                <div class="relative">
                    <select id="master_link" name="master_link" class="w-full appearance-none rounded-xl border-none bg-white py-2.5 pl-4 pr-10 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                        <option value="">Maestro: Todos</option>
                        <option value="with_master" {{ request('master_link') === 'with_master' ? 'selected' : '' }}>Con maestro</option>
                        <option value="without_master" {{ request('master_link') === 'without_master' ? 'selected' : '' }}>Sin maestro</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-zinc-400 text-lg">expand_more</span>
                </div>
                <div class="relative">
                    <select id="per_page" name="per_page" class="w-full appearance-none rounded-xl border-none bg-white py-2.5 pl-4 pr-10 text-sm shadow-sm focus:ring-1 focus:ring-primary">
                        @foreach ($pageSizes as $pageSize)
                            <option value="{{ $pageSize }}" {{ $selectedPerPage === (string) $pageSize ? 'selected' : '' }}>
                                {{ $pageSize === 'all' ? 'Todos' : number_format((int) $pageSize) }} por página
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-2.5 text-zinc-400 text-lg">expand_more</span>
                </div>
                <button type="submit" class="btn-primary">Aplicar filtros</button>
                <a href="{{ $clearUrl }}" class="inline-flex w-full items-center justify-center gap-1 text-sm font-bold text-primary hover:underline sm:w-auto sm:justify-start">
                    <span class="material-symbols-outlined text-lg">filter_list_off</span>
                    Limpiar
                </a>
            </form>

            @if (! empty($activeFilters))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($activeFilters as $filterLabel)
                        <span class="mpsfp-pill status-blue">{{ $filterLabel }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-surface-container-low">
                        <th class="bg-zinc-100 px-6 py-4">
                            <input class="rounded text-primary focus:ring-primary-container" type="checkbox">
                        </th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Nombre</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Resumen</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Descripción</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Categorias (x,y,z...)</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Precio impuestos incluidos</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Precio de coste</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">ID regla de impuestos</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Referencia Nº</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Nº Referencia proveedor</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Proveedor</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Marca</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Almacen</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">EAN13</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Cantidad</th>
                        <th class="bg-white px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Etiquetas (x,y,z...)</th>
                        <th class="bg-zinc-50 px-6 py-4 text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">URLs de las imagenes (x,y,z...)</th>
                        <th class="bg-zinc-100 px-6 py-4 text-right text-[0.6875rem] font-bold uppercase tracking-wider text-zinc-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-50">
                    @forelse ($products as $product)
                        @php
                            $imageUrls = collect($product->image_urls ?? [])
                                ->filter(fn ($url) => filled($url))
                                ->map(fn ($url) => html_entity_decode(trim((string) $url), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                                ->unique(function ($url) {
                                    $parts = parse_url((string) $url);
                                    if ($parts === false) {
                                        return (string) $url;
                                    }

                                    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                                    $host = strtolower((string) ($parts['host'] ?? ''));
                                    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                                    $path = rawurldecode((string) ($parts['path'] ?? ''));
                                    $path = preg_replace('#/+#', '/', $path) ?? $path;

                                    return "{$scheme}://{$host}{$port}{$path}";
                                })
                                ->values()
                                ->all();
                            $thumbUrl = $imageUrls[0] ?? null;
                            $validationClass = match ($product->validation_status) {
                                'ok' => 'status-green',
                                'warning' => 'status-amber',
                                'error' => 'status-red',
                                default => 'status-gray',
                            };
                            $validationLabel = match ($product->validation_status) {
                                'ok' => 'Validado',
                                'warning' => 'Pendiente',
                                'error' => 'Error',
                                default => 'Sin validar',
                            };
                            $barcodeClass = match ($product->barcode_status) {
                                'ok' => 'status-green',
                                'non_ean' => 'status-blue',
                                'invalid_ean' => 'status-amber',
                                'missing' => 'status-red',
                                default => 'status-gray',
                            };
                            $displaySummary = trim((string) ($product->summary ?? ''));
                            $displayName = trim((string) ($product->name ?? ''));
                            $supplierReference = trim((string) ($product->supplier_reference ?? ''));
                            if ($supplierReference !== '') {
                                $escapedRef = preg_quote($supplierReference, '/');
                                $displayName = trim((string) preg_replace('/^' . $escapedRef . '\s*[-:|]\s*/u', '', $displayName));
                                $displaySummary = trim((string) preg_replace('/^' . $escapedRef . '\s*[-:|]\s*/u', '', $displaySummary));
                            }
                            $summaryWordCount = str_word_count(str_replace(['-', '_', '/'], ' ', $displaySummary));
                            $summaryLooksLikeCode = $displaySummary !== '' && ! str_contains($displaySummary, ' ') && preg_match('/^[A-Z0-9][A-Z0-9._\\/-]{3,}$/', $displaySummary);
                            if (($displaySummary === '' || $summaryLooksLikeCode || $summaryWordCount < 2) && $displayName !== '') {
                                $displaySummary = $displayName;
                            }
                            $importUrl = null;
                            if ($product->supplierImport) {
                                $importUrl = isset($mpsfpProject)
                                    ? route('projects.mpsfp.imports.show', [$mpsfpProject, $product->supplierImport])
                                    : route('imports.show', $product->supplierImport);
                            }
                            $masterUrl = null;
                            if ($product->masterProduct) {
                                $masterUrl = isset($mpsfpProject)
                                    ? route('projects.mpsfp.master.show', [$mpsfpProject, $product->masterProduct])
                                    : route('products.master.show', $product->masterProduct);
                            }
                            $categoryParts = collect($product->formattedCategoryPath() !== '' ? explode(', ', $product->formattedCategoryPath()) : [])
                                ->map(fn ($item) => trim((string) $item))
                                ->filter()
                                ->values();
                            $categoryPreview = $categoryParts->isNotEmpty()
                                ? $categoryParts->take(-2)->implode(' / ')
                                : null;
                            $categoryRouteText = $categoryParts->implode(', ');
                            $categorySuggested = strtolower((string) ($product->category_status ?? 'unassigned')) === 'suggested';
                            $detailUrl = isset($mpsfpProject)
                                ? route('projects.mpsfp.normalized.show', [$mpsfpProject, $product])
                                : route('products.normalized.show', $product);
                            $detailModalUrl = $detailUrl . '?modal=1';
                            $formattedSalePrice = $product->price_tax_incl !== null
                                ? number_format((float) $product->price_tax_incl, 2, ',', '.') . ' €'
                                : '';
                            $formattedCostPrice = $product->cost_price !== null
                                ? number_format((float) $product->cost_price, 2, ',', '.') . ' €'
                                : '';
                            $descriptionText = trim((string) ($product->description ?? ''));
                            $descriptionPreview = \Illuminate\Support\Str::limit($descriptionText, 220);
                            $summaryPreview = \Illuminate\Support\Str::limit($displaySummary, 180);
                            $masterReference = trim((string) ($product->masterProduct->reference ?? ''));
                            $tagsText = collect(preg_split('/\s*,\s*/u', (string) ($product->tags ?? '')) ?: [])
                                ->map(fn ($tag) => trim((string) $tag))
                                ->filter()
                                ->implode(', ');
                            $imageUrlsText = implode(', ', $imageUrls);
                        @endphp
                        <tr class="transition-colors hover:bg-zinc-50">
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <input class="rounded text-primary focus:ring-primary-container" type="checkbox">
                            </td>
                            <td class="min-w-[18rem] bg-white px-6 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-surface-container-low p-1">
                                        @if ($thumbUrl)
                                            <button type="button"
                                                    class="block h-full w-full"
                                                    data-open-image-modal
                                                    data-images='@json($imageUrls)'
                                                    data-start-index="0"
                                                    data-title="{{ $product->name ?? 'Producto' }}">
                                                <img src="{{ $thumbUrl }}" alt="{{ $product->name ?? 'Producto' }}" class="h-full w-full rounded object-contain">
                                            </button>
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[10px] font-semibold text-zinc-400">Sin imagen</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-on-surface">{{ $displayName !== '' ? $displayName : '—' }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @if (($product->same_ean_suppliers_count ?? 0) > 1)
                                                <span class="status-badge status-pink">{{ (int) $product->same_ean_suppliers_count }} proveedores</span>
                                            @endif
                                            @if (($product->product_ean_issues_count ?? 0) > 0)
                                                <span class="status-badge status-red">
                                                    {{ $product->product_ean_issues_count }} {{ (int) $product->product_ean_issues_count === 1 ? 'incidencia' : 'incidencias' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                @if ($summaryPreview !== '')
                                    <p class="max-w-md whitespace-pre-line text-sm text-zinc-600">{{ $summaryPreview }}</p>
                                @else
                                    <p class="text-sm text-zinc-400">Sin resumen</p>
                                @endif
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                @if ($descriptionPreview !== '')
                                    <p class="max-w-lg whitespace-pre-line text-sm text-zinc-600">{{ $descriptionPreview }}</p>
                                @else
                                    <p class="text-sm text-zinc-400">Sin descripción</p>
                                @endif
                            </td>
                            <td class="min-w-[18rem] bg-zinc-50/70 px-6 py-4 align-top">
                                @if ($categoryRouteText !== '')
                                    <p class="max-w-md break-words text-sm text-zinc-600" title="{{ $categoryRouteText }}">{{ $categoryRouteText }}</p>
                                @else
                                    <span class="text-xs text-zinc-400">Sin sugerencia</span>
                                @endif
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                <p class="text-sm font-semibold text-primary">{{ $formattedSalePrice }}</p>
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <p class="text-sm font-medium text-on-surface">{{ $formattedCostPrice }}</p>
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                <p class="font-mono text-xs text-zinc-600">{{ $product->tax_rule_id ?? '' }}</p>
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <p class="font-mono text-xs text-zinc-600">{{ $masterReference }}</p>
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                <p class="font-mono text-xs text-zinc-600">{{ $supplierReference }}</p>
                                @if ($importUrl)
                                    <a href="{{ $importUrl }}" class="mt-1 inline-flex text-[0.6875rem] font-semibold text-zinc-400 hover:text-primary">
                                        Ver importación
                                    </a>
                                @endif
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <p class="text-sm font-semibold text-on-surface">{{ $product->supplier->name ?? '—' }}</p>
                                @if ($product->supplier?->slug)
                                    <p class="mt-1 text-[10px] uppercase tracking-wide text-zinc-400">{{ $product->supplier->slug }}</p>
                                @endif
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                <p class="text-sm text-zinc-600">{{ $product->brand ?? '' }}</p>
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <p class="text-sm text-zinc-600">{{ $product->warehouse ?? '' }}</p>
                            </td>
                            <td class="bg-white px-6 py-4 align-top">
                                @if ($product->ean13)
                                    <p class="font-mono text-xs font-bold text-on-secondary-container">{{ $product->ean13 }}</p>
                                @else
                                    <p class="font-mono text-xs text-zinc-400"></p>
                                @endif
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 align-top">
                                <p class="font-mono text-xs text-zinc-600">{{ number_format((int) ($product->quantity ?? 0)) }}</p>
                            </td>
                            <td class="min-w-[14rem] bg-white px-6 py-4 align-top">
                                @if ($tagsText !== '')
                                    <p class="max-w-sm break-words text-sm text-zinc-600">{{ $tagsText }}</p>
                                @else
                                    <p class="text-sm text-zinc-400">Sin etiquetas</p>
                                @endif
                            </td>
                            <td class="min-w-[20rem] bg-zinc-50/70 px-6 py-4 align-top">
                                @if ($imageUrlsText !== '')
                                    <p class="max-w-lg break-all text-xs leading-5 text-zinc-600">{{ $imageUrlsText }}</p>
                                @else
                                    <p class="text-sm text-zinc-400">Sin imágenes</p>
                                @endif
                            </td>
                            <td class="bg-zinc-50/70 px-6 py-4 text-right align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            class="rounded p-1.5 text-primary transition-colors hover:bg-primary/10 hover:text-primary"
                                            title="Vista rápida"
                                            data-open-detail-modal
                                            data-detail-url="{{ $detailModalUrl }}"
                                            data-page-url="{{ $detailUrl }}"
                                            data-title="{{ $product->name ?? 'Producto' }}">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </button>
                                    <a href="{{ $detailUrl }}" class="rounded p-1.5 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700" title="Abrir ficha">
                                        <span class="material-symbols-outlined text-lg">open_in_new</span>
                                    </a>
                                    @if ($masterUrl)
                                        <a href="{{ $masterUrl }}" class="rounded p-1.5 text-tertiary transition-colors hover:bg-green-50 hover:text-green-700" title="Ver maestro">
                                            <span class="material-symbols-outlined text-lg">database</span>
                                        </a>
                                    @endif
                                    @if ($importUrl)
                                        <a href="{{ $importUrl }}" class="rounded p-1.5 text-amber-600 transition-colors hover:bg-amber-50 hover:text-amber-700" title="Ver importación">
                                            <span class="material-symbols-outlined text-lg">upload_file</span>
                                        </a>
                                    @endif
                                    @can('delete', $product)
                                        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.normalized.destroy', [$mpsfpProject, $product]) : route('products.normalized.destroy', $product) }}" method="POST" onsubmit="return confirm('Se eliminará el producto normalizado seleccionado. ¿Continuar?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded p-1.5 text-zinc-300 transition-colors hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="px-6 py-10 text-center">
                                <div class="mx-auto max-w-xl">
                                    <p class="text-sm font-semibold text-[#555555]">No hay productos que coincidan con los filtros actuales.</p>
                                    <p class="mt-2 text-sm text-gray-500">Prueba a quitar filtros o cambia la combinación de proveedor, estado y maestro.</p>
                                    <a href="{{ $clearUrl }}" class="mt-4 inline-flex btn-secondary">Volver a la lista completa</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer border-t border-zinc-100 bg-zinc-50/30 px-6 py-4">
            <span class="text-xs text-zinc-500">
                Mostrando {{ $products->firstItem() ?? 0 }} a {{ $products->lastItem() ?? 0 }} de {{ number_format($products->total()) }} registros
            </span>
            <div class="flex justify-center">{{ $products->withQueryString()->links() }}</div>
        </div>
    </section>

    <div id="image-modal"
         class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/75 px-4 py-6">
        <div class="absolute inset-0" data-close-image-modal></div>
        <div class="relative z-10 w-full max-w-6xl overflow-hidden rounded-[1.75rem] border border-white/10 bg-white shadow-2xl">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Galería del proveedor</p>
                    <h3 id="image-modal-title" class="mt-1 truncate text-lg font-semibold text-[#555555]">Imágenes del producto</h3>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <span id="image-modal-count" class="status-badge status-blue">Sin imágenes</span>
                    <button type="button" class="btn-secondary" data-close-image-modal>Cerrar</button>
                </div>
            </div>

            <div class="grid gap-0 lg:grid-cols-[1fr_280px]">
                <div class="relative flex min-h-[420px] items-center justify-center bg-slate-100 px-4 py-6">
                    <img id="image-modal-current"
                         src=""
                         alt="Imagen del producto"
                         class="max-h-[70vh] w-full object-contain">

                    <button type="button"
                            id="image-modal-prev"
                            class="absolute left-4 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/90 px-4 py-3 text-sm font-semibold text-[#555555] shadow">
                        ‹
                    </button>
                    <button type="button"
                            id="image-modal-next"
                            class="absolute right-4 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/90 px-4 py-3 text-sm font-semibold text-[#555555] shadow">
                        ›
                    </button>
                </div>

                <div class="border-t border-gray-200 bg-white p-4 lg:border-l lg:border-t-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Miniaturas</p>
                    <div id="image-modal-thumbnails" class="mt-4 grid max-h-[70vh] grid-cols-3 gap-3 overflow-y-auto"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="detail-modal"
         class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/75 px-4 py-6">
        <div class="absolute inset-0" data-close-detail-modal></div>
        <div class="relative z-10 flex h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-[1.75rem] border border-white/10 bg-slate-50 shadow-2xl">
            <div class="flex flex-col gap-3 border-b border-gray-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Ficha completa en modal</p>
                    <h3 id="detail-modal-title" class="mt-1 truncate text-lg font-semibold text-[#555555]">Ficha del producto</h3>
                </div>
                <div class="action-stack">
                    <a id="detail-modal-page-link" href="#" class="btn-secondary">Abrir página</a>
                    <button type="button" class="btn-primary" data-close-detail-modal>Cerrar</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6">
                <div id="detail-modal-loading" class="flex min-h-[240px] items-center justify-center hidden">
                    <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5 text-sm text-gray-500 shadow-sm">
                        Cargando ficha del producto...
                    </div>
                </div>

                <div id="detail-modal-error" class="alert alert-error hidden"></div>

                <div id="detail-modal-content" class="space-y-4"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const imageModal = document.getElementById('image-modal');
        const imageModalTitle = document.getElementById('image-modal-title');
        const imageModalCount = document.getElementById('image-modal-count');
        const imageModalCurrent = document.getElementById('image-modal-current');
        const imageModalThumbs = document.getElementById('image-modal-thumbnails');
        const imageModalPrev = document.getElementById('image-modal-prev');
        const imageModalNext = document.getElementById('image-modal-next');

        const detailModal = document.getElementById('detail-modal');
        const detailModalTitle = document.getElementById('detail-modal-title');
        const detailModalPageLink = document.getElementById('detail-modal-page-link');
        const detailModalLoading = document.getElementById('detail-modal-loading');
        const detailModalError = document.getElementById('detail-modal-error');
        const detailModalContent = document.getElementById('detail-modal-content');

        let imageModalImages = [];
        let imageModalIndex = 0;

        const lockBody = () => { document.body.style.overflow = 'hidden'; };
        const unlockBody = () => { document.body.style.overflow = ''; };

        const renderImageModal = () => {
            const current = imageModalImages[imageModalIndex] ?? '';
            imageModalCurrent.src = current;
            imageModalCurrent.alt = current ? `Imagen ${imageModalIndex + 1}` : 'Sin imagen';
            imageModalCount.textContent = imageModalImages.length
                ? `${imageModalIndex + 1} / ${imageModalImages.length}`
                : 'Sin imágenes';

            imageModalPrev.classList.toggle('hidden', imageModalImages.length <= 1);
            imageModalNext.classList.toggle('hidden', imageModalImages.length <= 1);

            imageModalThumbs.innerHTML = '';
            imageModalImages.forEach((url, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `overflow-hidden rounded-2xl border bg-gray-50 transition ${index === imageModalIndex ? 'border-[#E6007E] ring-2 ring-[#E6007E]/20' : 'border-gray-200 hover:border-[#E6007E]/30'}`;
                button.addEventListener('click', () => {
                    imageModalIndex = index;
                    renderImageModal();
                });

                const img = document.createElement('img');
                img.src = url;
                img.alt = `Miniatura ${index + 1}`;
                img.className = 'aspect-square h-full w-full object-cover';
                button.appendChild(img);
                imageModalThumbs.appendChild(button);
            });
        };

        const openImageModal = (images, index, title) => {
            imageModalImages = Array.isArray(images) ? images.filter(Boolean) : [];
            if (!imageModalImages.length) {
                return;
            }

            imageModalIndex = Number.isInteger(index) ? index : 0;
            imageModalTitle.textContent = title || 'Imágenes del producto';
            renderImageModal();
            imageModal.classList.remove('hidden');
            imageModal.classList.add('flex');
            lockBody();
        };

        const closeImageModal = () => {
            imageModal.classList.add('hidden');
            imageModal.classList.remove('flex');
            imageModalImages = [];
            imageModalIndex = 0;
            imageModalThumbs.innerHTML = '';
            imageModalCurrent.src = '';
            unlockBody();
        };

        const openDetailModal = async (detailUrl, pageUrl, title) => {
            detailModal.classList.remove('hidden');
            detailModal.classList.add('flex');
            detailModalTitle.textContent = title || 'Ficha del producto';
            detailModalPageLink.href = pageUrl || '#';
            detailModalLoading.classList.remove('hidden');
            detailModalError.classList.add('hidden');
            detailModalError.textContent = '';
            detailModalContent.innerHTML = '';
            lockBody();

            try {
                const response = await fetch(detailUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('No se pudo cargar la ficha del producto.');
                }

                detailModalContent.innerHTML = await response.text();
                if (window.initNormalizedProductGalleries) {
                    window.initNormalizedProductGalleries(detailModalContent);
                }
            } catch (error) {
                detailModalError.textContent = error?.message || 'No se pudo cargar la ficha del producto.';
                detailModalError.classList.remove('hidden');
            } finally {
                detailModalLoading.classList.add('hidden');
            }
        };

        const closeDetailModal = () => {
            detailModal.classList.add('hidden');
            detailModal.classList.remove('flex');
            detailModalLoading.classList.add('hidden');
            detailModalError.classList.add('hidden');
            detailModalError.textContent = '';
            detailModalContent.innerHTML = '';
            detailModalPageLink.href = '#';
            unlockBody();
        };

        document.addEventListener('click', (event) => {
            const imageTrigger = event.target.closest('[data-open-image-modal]');
            if (imageTrigger) {
                event.preventDefault();
                openImageModal(
                    JSON.parse(imageTrigger.dataset.images || '[]'),
                    parseInt(imageTrigger.dataset.startIndex || '0', 10),
                    imageTrigger.dataset.title || 'Imágenes del producto'
                );
                return;
            }

            const detailTrigger = event.target.closest('[data-open-detail-modal]');
            if (detailTrigger) {
                event.preventDefault();
                openDetailModal(
                    detailTrigger.dataset.detailUrl,
                    detailTrigger.dataset.pageUrl,
                    detailTrigger.dataset.title || 'Ficha del producto'
                );
                return;
            }

            if (event.target.closest('[data-close-image-modal]')) {
                event.preventDefault();
                closeImageModal();
                return;
            }

            if (event.target.closest('[data-close-detail-modal]')) {
                event.preventDefault();
                closeDetailModal();
            }
        });

        imageModalPrev.addEventListener('click', () => {
            if (!imageModalImages.length) return;
            imageModalIndex = (imageModalIndex - 1 + imageModalImages.length) % imageModalImages.length;
            renderImageModal();
        });

        imageModalNext.addEventListener('click', () => {
            if (!imageModalImages.length) return;
            imageModalIndex = (imageModalIndex + 1) % imageModalImages.length;
            renderImageModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeImageModal();
                closeDetailModal();
            }
        });
    });
</script>
@endpush

@include('products.normalized._gallery_script')
