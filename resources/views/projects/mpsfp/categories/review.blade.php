@extends('layouts.app')

@section('title', 'MPSFP · Revisión de categorías')
@section('page_title', 'MPSFP · Revisión de categorías')

@section('content')
@php
    $filterAction = route('projects.mpsfp.categories.review', $mpsfpProject);
    $bulkApplyAction = route('projects.mpsfp.categories.bulk-apply', $mpsfpProject);
    $statusLabels = [
        'pending' => 'Pendientes de asignar',
        'assigned' => 'Con categoría asignada',
        'all' => 'Todos con sugerencias',
    ];
@endphp

<div class="space-y-4">
    @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
    @include('projects.mpsfp._context', [
        'project' => $mpsfpProject,
        'label' => 'Categorías',
        'title' => 'MPSFP / Revisión de categorías',
        'subtitle' => 'Aquí revisas sugerencias del matcher sobre productos normalizados y decides qué categoría final se acepta para cada producto.',
    ])

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Árbol maestro</p>
            <p class="mt-2 text-3xl font-bold text-[#555555]">{{ number_format(\App\Models\Category::count()) }}</p>
            <p class="mt-2 text-sm text-gray-500">Categorías cargadas desde tu Excel maestro de PrestaShop.</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Productos listados</p>
            <p class="mt-2 text-3xl font-bold text-[#555555]">{{ number_format($products->total()) }}</p>
            <p class="mt-2 text-sm text-gray-500">{{ $statusLabels[$reviewStatus] ?? 'Revisión actual' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Qué hace esta pantalla</p>
            <p class="mt-2 text-sm text-gray-600">Aquí solo se sugieren rutas probables. La confirmación final de la categoría debe hacerse desde la app cuando el usuario empiece a trabajar el stock del producto.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ $filterAction }}" class="grid gap-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Buscar</label>
                <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Nombre, resumen, EAN o ref. proveedor" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            </div>
            <div>
                <label for="supplier_id" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Proveedor</label>
                <select id="supplier_id" name="supplier_id" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Estado</label>
                <select id="status" name="status" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="pending" {{ $reviewStatus === 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="assigned" {{ $reviewStatus === 'assigned' ? 'selected' : '' }}>Asignados</option>
                    <option value="all" {{ $reviewStatus === 'all' ? 'selected' : '' }}>Todos</option>
                </select>
            </div>
            <div>
                <label for="min_score" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Score mínimo</label>
                <input id="min_score" type="number" step="0.01" min="0" name="min_score" value="{{ request('min_score', $minScore) }}" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            </div>
            <div class="action-stack xl:col-span-6">
                <button type="submit" id="apply-filters-button" class="btn-primary">Aplicar filtros</button>
                <a href="{{ $filterAction }}" class="btn-secondary">Quitar filtros</a>
                <button
                    type="button"
                    id="master-tree-button"
                    class="btn-secondary"
                    onclick="mpOpenMasterTree()"
                >
                    Árbol maestro
                </button>
            </div>
        </form>
    </div>

    <form method="POST" action="{{ $bulkApplyAction }}" id="category-bulk-form" class="space-y-4">
        @csrf
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" id="category-select-all" class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                        <span>Seleccionar todo en esta página</span>
                    </label>
                    <span id="category-selected-count">0 seleccionados</span>
                </div>
                <button type="submit" class="btn-primary">Aplicar mejor sugerencia a seleccionados</button>
            </div>
        </div>

        <div class="space-y-4">
        @forelse ($products as $product)
            @php
                $topSuggestions = $product->productCategorySuggestions->take(5);
                $detailUrl = route('projects.mpsfp.normalized.show', [$mpsfpProject, $product]);
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <label class="mb-3 inline-flex items-center gap-2 text-sm text-gray-500">
                            <input type="checkbox" name="normalized_product_ids[]" value="{{ $product->id }}" class="category-row-checkbox rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                            <span>Seleccionar producto</span>
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-[#555555]">{{ $product->name ?? 'Sin nombre' }}</h3>
                            @if ($product->category_id)
                                <span class="status-badge status-green">Categoría aplicada</span>
                            @else
                                <span class="status-badge status-amber">Pendiente</span>
                            @endif
                            <span class="status-badge status-blue">{{ $product->product_category_suggestions_count }} sugerencias</span>
                            @if ($product->top_category_score)
                                <span class="status-badge status-gray">Top {{ number_format((float) $product->top_category_score, 2) }}</span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                            <span>Proveedor: {{ $product->supplier->name ?? '—' }}</span>
                            <span>Ref. proveedor: {{ $product->supplier_reference ?? '—' }}</span>
                            @if ($product->ean13)
                                <span>EAN: {{ $product->ean13 }}</span>
                            @endif
                            @if ($product->masterProduct)
                                <span>Con maestro</span>
                            @endif
                        </div>
                        @if ($product->formattedCategoryPath())
                            <p class="mt-2 text-sm text-gray-600">Ruta actual: {{ $product->formattedCategoryPath() }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $detailUrl }}" class="btn-secondary">Abrir ficha</a>
                        <a href="{{ $detailUrl }}?modal=1" class="btn-secondary">Vista modal</a>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($topSuggestions as $suggestion)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-[#555555]">{{ $suggestion->category->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Padre: {{ $suggestion->category->parent->name ?? 'Raíz' }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="status-badge status-blue">Score {{ number_format((float) $suggestion->score, 2) }}</span>
                                    @if ($suggestion->accepted_at)
                                        <span class="status-badge status-green">Seleccionada</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 rounded-xl border border-[#E6007E]/15 bg-[#E6007E]/5 px-3 py-3 text-sm text-[#555555]">
                                Sugerencia disponible para revisión. La confirmación final de esta ruta se hará desde la app móvil durante el flujo de stock/categoría.
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div id="no-suggestions-message" class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-semibold text-[#555555]">No hay productos con sugerencias para los filtros actuales.</p>
                <p class="mt-2 text-sm text-gray-500">Ajusta la búsqueda, cambia el proveedor o espera a que termine la generación completa de sugerencias.</p>
            </div>

            <div id="master-tree-panel" class="hidden mt-4 rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-headline text-lg font-bold text-[#555555]">Árbol maestro</p>
                        <p class="mt-1 text-sm text-gray-500">Categorías y subcategorías (expandir/contraer).</p>
                    </div>
                    <button type="button" class="btn-secondary" onclick="mpCloseMasterTree()">Cerrar</button>
                </div>

                <div class="mt-4">
                    @include('categories._master_tree_embed', ['roots' => $roots, 'childrenByParent' => $childrenByParent])
                </div>
            </div>
        @endforelse
        </div>
    </form>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <p class="text-sm text-gray-500">
            Mostrando {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} de {{ number_format($products->total()) }} productos con sugerencias.
        </p>
        <div class="flex justify-center">{{ $products->withQueryString()->links() }}</div>
    </div>
</div>
<script>
    (() => {
        const form = document.getElementById('category-bulk-form');
        if (!form) return;
        const selectAll = document.getElementById('category-select-all');
        const checkboxes = Array.from(form.querySelectorAll('.category-row-checkbox'));
        const counter = document.getElementById('category-selected-count');

        const syncCount = () => {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (counter) counter.textContent = `${selected} seleccionados`;
            if (selectAll) selectAll.checked = selected > 0 && selected === checkboxes.length;
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
                syncCount();
            });
        }

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncCount));
        syncCount();
    })();

    function mpOpenMasterTree() {
        const panel = document.getElementById('master-tree-panel');
        const btn = document.getElementById('master-tree-button');
        const emptyMsg = document.getElementById('no-suggestions-message');
        const applyBtn = document.getElementById('apply-filters-button');
        if (!panel) return;
        panel.classList.remove('hidden');
        panel.style.display = 'block';
        if (emptyMsg) {
            emptyMsg.classList.add('hidden');
        }
        if (applyBtn) {
            applyBtn.setAttribute('disabled', 'disabled');
            applyBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }
        if (btn) {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
        }
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function mpCloseMasterTree() {
        const panel = document.getElementById('master-tree-panel');
        const btn = document.getElementById('master-tree-button');
        const emptyMsg = document.getElementById('no-suggestions-message');
        const applyBtn = document.getElementById('apply-filters-button');
        if (!panel) return;
        panel.classList.add('hidden');
        panel.style.display = 'none';
        if (emptyMsg) {
            emptyMsg.classList.remove('hidden');
        }
        if (applyBtn) {
            applyBtn.removeAttribute('disabled');
            applyBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }
        if (btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        }
    }
</script>
@endsection
