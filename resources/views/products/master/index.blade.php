@extends('layouts.app')

@section('title', 'Productos maestros')
@section('page_title', 'Productos maestros')

@section('content')
@php
    $filterAction = isset($mpsfpProject) ? route('projects.mpsfp.master.index', $mpsfpProject) : route('products.master.index');
    $bulkApproveAction = isset($mpsfpProject) ? route('projects.mpsfp.master.bulk-approve', $mpsfpProject) : route('products.master.bulk-approve');
    $bulkUnapproveAction = isset($mpsfpProject) ? route('projects.mpsfp.master.bulk-unapprove', $mpsfpProject) : route('products.master.bulk-unapprove');
    $totalProducts = $products->total();
    $approvedOnPage = $products->getCollection()->where('is_approved', true)->count();
    $pendingOnPage = $products->getCollection()->where('is_approved', false)->count();
    $canBulkApprove = auth()->user()->can('master_products.approve');
@endphp

<div class="space-y-6">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Maestros',
            'title' => 'MPSFP / Catálogo maestro',
            'subtitle' => 'Esta es la capa final operativa antes de exportación: aprobación, stock operativo, categoría consolidada y referencia final.',
        ])
    @endif

    <section class="mpsfp-shell p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Repositorio consolidado</p>
                <h3 class="mt-2 font-headline text-3xl font-extrabold tracking-tight text-mptext">Catálogo maestro de productos</h3>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-gray-500">Aquí vive el producto final de negocio. Desde esta vista puedes filtrar aprobados o pendientes, revisar la consolidación y decidir qué está listo para la exportación a PrestaShop.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (isset($mpsfpProject))
                    <a href="{{ route('projects.mpsfp.export.index', $mpsfpProject) }}" class="btn-secondary">Ir a exportación</a>
                @endif
            </div>
        </div>

        @php
            $exportableOnPage = $products->getCollection()->filter(fn ($product) => $product->price_tax_incl !== null && $product->cost_price !== null && (float) $product->price_tax_incl >= (float) $product->cost_price)->count();
            $withCategoryOnPage = $products->getCollection()->whereNotNull('category_id')->count();
        @endphp
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Total catálogo</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-mptext">{{ number_format($totalProducts) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Aprobados en esta vista</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-[#008a18]">{{ number_format($approvedOnPage) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Pendientes en esta vista</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-[#E6007E]">{{ number_format($pendingOnPage) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Exportables en esta vista</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-[#2563eb]">{{ number_format($exportableOnPage) }}</p>
            </div>
            <div class="mpsfp-kpi">
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Con categoría</p>
                <p class="mt-3 font-headline text-3xl font-extrabold text-[#0f766e]">{{ number_format($withCategoryOnPage) }}</p>
            </div>
        </div>
    </section>

    <section class="mpsfp-panel p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[0.68rem] font-extrabold uppercase tracking-[0.24em] text-gray-400">Filtros</p>
                <h3 class="mt-2 font-headline text-xl font-extrabold tracking-tight text-mptext">Busca y segmenta el catálogo maestro</h3>
            </div>
            <span class="mpsfp-pill status-blue">{{ number_format($products->firstItem() ?? 0) }}-{{ number_format($products->lastItem() ?? 0) }} de {{ number_format($products->total()) }}</span>
        </div>

        <form method="GET" action="{{ $filterAction }}" class="mt-5 grid gap-3 rounded-2xl border border-mpborder bg-[#faf8f8] p-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre, EAN o referencia..." class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            </div>
            <div>
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Estado</label>
                <select name="is_approved" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Aprobados</option>
                    <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>Pendientes</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Proveedor</label>
                <select name="supplier_id" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach (($suppliers ?? collect()) as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Listo para exportar</label>
                <select name="export_ready" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="1" {{ request('export_ready') === '1' ? 'selected' : '' }}>Sí</option>
                    <option value="0" {{ request('export_ready') === '0' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Categoría</label>
                <select name="with_category" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todas</option>
                    <option value="1" {{ request('with_category') === '1' ? 'selected' : '' }}>Con categoría</option>
                    <option value="0" {{ request('with_category') === '0' ? 'selected' : '' }}>Sin categoría</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-gray-400">Revisión EAN</label>
                <select name="ean_review" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="clean" {{ request('ean_review') === 'clean' ? 'selected' : '' }}>Sin incidencias</option>
                    <option value="issues" {{ request('ean_review') === 'issues' ? 'selected' : '' }}>Con incidencias</option>
                </select>
            </div>
            <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-5">
                <button type="submit" class="btn-primary">Filtrar</button>
                <a href="{{ $filterAction }}" class="btn-secondary">Quitar filtros</a>
            </div>
        </form>
    </section>

    <form method="POST" action="{{ $bulkApproveAction }}" id="master-bulk-form" class="mpsfp-panel overflow-hidden">
        @csrf
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-4">
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" id="master-select-all" class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                    <span>Seleccionar todo en esta página</span>
                </label>
                <span id="master-selected-count">0 seleccionados</span>
            </div>
            @if ($canBulkApprove)
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn-primary" formaction="{{ $bulkApproveAction }}">Aprobar seleccionados</button>
                    <button type="submit" class="btn-secondary" formaction="{{ $bulkUnapproveAction }}">Retirar seleccionados</button>
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="mpsfp-data-table min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <span class="sr-only">Seleccionar</span>
                        </th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-left">EAN / referencia</th>
                        <th class="px-4 py-3 text-left">Stock y categoría</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($products as $product)
                        <tr class="align-top">
                            <td class="px-4 py-4">
                                <input type="checkbox" name="master_product_ids[]" value="{{ $product->id }}" class="master-row-checkbox rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                            </td>
                            <td class="px-4 py-4">
                                <div class="max-w-md">
                                    <p class="text-sm font-bold text-mptext">{{ $product->name ?? '—' }}</p>
                                    @if ($product->brand)
                                        <p class="mt-1 text-xs text-gray-500">Marca: {{ $product->brand }}</p>
                                    @endif
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($product->price_tax_incl !== null && $product->cost_price !== null && (float) $product->price_tax_incl >= (float) $product->cost_price)
                                            <span class="mpsfp-pill status-blue">Exportable</span>
                                        @endif
                                        @if ($product->category_id)
                                            <span class="mpsfp-pill status-green">Con categoría</span>
                                        @else
                                            <span class="mpsfp-pill status-gray">Sin categoría</span>
                                        @endif
                                        @if ($product->normalizedProducts->contains(fn ($np) => $np->productEanIssues->contains(fn ($issue) => $issue->resolved_at === null)))
                                            <span class="mpsfp-pill status-amber">Revisar EAN</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                <div class="space-y-2">
                                    <span class="mpsfp-pill {{ $product->ean13 ? 'status-green' : 'status-gray' }}">{{ $product->ean13 ? 'EAN ' . $product->ean13 : 'Sin EAN' }}</span>
                                    <span class="mpsfp-pill status-gray">Ref: {{ $product->reference ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                <div class="space-y-2">
                                    <span class="mpsfp-pill status-blue">Stock: {{ $product->quantity ?? 0 }}</span>
                                    <p class="max-w-sm text-xs text-gray-500">{{ $product->category->name ?? 'Sin categoría final' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if ($product->is_approved)
                                    <span class="mpsfp-pill status-green">Aprobado</span>
                                @else
                                    <span class="mpsfp-pill status-amber">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right text-sm">
                                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.show', [$mpsfpProject, $product]) : route('products.master.show', $product) }}" class="btn-link">Ver</a>
                                @can('approve', $product)
                                    @if ($product->is_approved)
                                        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.unapprove', [$mpsfpProject, $product]) : route('products.master.unapprove', $product) }}" method="POST" class="ml-2 inline-block">
                                            @csrf
                                            <button type="submit" class="btn-link-muted">Retirar</button>
                                        </form>
                                    @else
                                        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.approve', [$mpsfpProject, $product]) : route('products.master.approve', $product) }}" method="POST" class="ml-2 inline-block">
                                            @csrf
                                            <button type="submit" class="btn-link">Aprobar</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $product)
                                    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.destroy', [$mpsfpProject, $product]) : route('products.master.destroy', $product) }}" method="POST" class="ml-2 inline-block" onsubmit="return confirm('Se eliminará el producto maestro seleccionado. ¿Continuar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-link-muted">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No hay productos maestros todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="flex justify-center">{{ $products->withQueryString()->links() }}</div>
</div>

<script>
    (() => {
        const form = document.getElementById('master-bulk-form');
        if (!form) return;
        const selectAll = document.getElementById('master-select-all');
        const checkboxes = Array.from(form.querySelectorAll('.master-row-checkbox'));
        const counter = document.getElementById('master-selected-count');

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
</script>
@endsection
