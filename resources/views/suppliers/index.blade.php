@extends('layouts.app')

@section('title', 'Proveedores')
@section('page_title', 'Proveedores')

@section('content')
@php
    $canCreateSupplier = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.proveedores.actions.create', false)
        : auth()->user()->can('suppliers.create');
    $canEditSupplier = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.proveedores.actions.edit', false)
        : auth()->user()->can('suppliers.edit');
    $canDeleteSupplier = $canEditSupplier;
    $stats = $supplierStats ?? [
        'total' => $suppliers->total(),
        'active' => 0,
        'inactive' => 0,
        'recent' => 0,
    ];
    $indexRoute = isset($mpsfpProject) ? route('projects.mpsfp.suppliers.index', $mpsfpProject) : route('suppliers.index');
@endphp

<div class="space-y-8">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
    @endif

    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div class="space-y-1">
                    <nav class="flex items-center gap-2 text-[11px] uppercase tracking-widest text-secondary font-bold">
                        <span>Dashboard</span>
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        <span>Proyectos</span>
                        @if (isset($mpsfpProject))
                            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                            <span>{{ $mpsfpProject->name }}</span>
                        @endif
                        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        <span class="text-primary">Proveedores</span>
                    </nav>
                    <h2 class="font-headline font-extrabold text-3xl text-on-surface tracking-tight">Gestión de proveedores</h2>
                </div>

                @if ($canCreateSupplier)
                    <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.create', $mpsfpProject) : route('suppliers.create') }}" class="btn-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Nuevo proveedor
                    </a>
                @endif
            </div>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="mpsfp-kpi border-l-4 border-primary">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-secondary">Total proveedores</p>
                    <p class="font-headline text-2xl font-black text-on-surface">{{ number_format($stats['total']) }}</p>
                    <p class="mt-2 flex items-center gap-1 text-[10px] font-bold text-tertiary">
                        <span class="material-symbols-outlined text-sm">trending_up</span> catálogo activo
                    </p>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-secondary">Activos</p>
                    <p class="font-headline text-2xl font-black text-on-surface">{{ number_format($stats['active']) }}</p>
                    <p class="mt-2 text-[10px] font-bold text-secondary">Disponibles para importar</p>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-secondary">Inactivos</p>
                    <p class="font-headline text-2xl font-black text-on-surface">{{ number_format($stats['inactive']) }}</p>
                    <p class="mt-2 text-[10px] font-bold text-secondary">Pendientes o pausados</p>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-secondary">Altas recientes</p>
                    <p class="font-headline text-2xl font-black text-on-surface">{{ number_format($stats['recent']) }}</p>
                    <p class="mt-2 text-[10px] font-bold text-secondary">Últimos 7 días</p>
                </div>
            </section>
        </div>
    </section>

    <section class="mpsfp-panel p-4">
        <form method="GET" action="{{ $indexRoute }}" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-lg">filter_list</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filtrar por nombre, código o slug..." class="w-full rounded-lg border-none bg-surface-container-low py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/20">
                </div>
                <div class="flex w-full items-center overflow-x-auto rounded-lg bg-surface-container-low p-1 md:w-auto md:overflow-visible">
                    <a href="{{ $indexRoute }}" class="px-4 py-1 text-xs font-bold rounded-md {{ request('is_active') === null ? 'bg-white text-on-surface shadow-sm' : 'text-secondary hover:text-on-surface' }}">Todos</a>
                    <a href="{{ $indexRoute }}?{{ http_build_query(array_filter(array_merge(request()->except('page', 'is_active'), ['is_active' => '1']))) }}" class="px-4 py-1 text-xs font-bold rounded-md {{ request('is_active') === '1' ? 'bg-white text-on-surface shadow-sm' : 'text-secondary hover:text-on-surface' }}">Activos</a>
                    <a href="{{ $indexRoute }}?{{ http_build_query(array_filter(array_merge(request()->except('page', 'is_active'), ['is_active' => '0']))) }}" class="px-4 py-1 text-xs font-bold rounded-md {{ request('is_active') === '0' ? 'bg-white text-on-surface shadow-sm' : 'text-secondary hover:text-on-surface' }}">Inactivos</a>
                </div>
            </div>

            <div class="action-stack">
                <a href="{{ $indexRoute }}" class="btn-secondary">Restablecer</a>
                <button type="submit" class="btn-primary">Aplicar filtros</button>
            </div>
        </form>
    </section>

    <section class="mpsfp-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-surface-container-low/50">
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-secondary">Nombre del proveedor</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-secondary">Slug</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-secondary">Código</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-secondary">Estado</th>
                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-secondary">Actividad</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-wider text-secondary">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse ($suppliers as $supplier)
                        <tr class="transition-colors hover:bg-surface-container-low/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded bg-surface-container-high text-xs font-bold text-primary">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($supplier->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-semibold">{{ $supplier->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-secondary">{{ $supplier->slug }}</td>
                            <td class="px-6 py-4 text-sm font-medium">{{ $supplier->code ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $supplier->is_active ? 'bg-tertiary-container text-on-tertiary' : 'bg-error-container text-on-error-container' }}">
                                    {{ $supplier->is_active ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-secondary">
                                <div class="space-y-1">
                                    <p>{{ number_format($supplier->supplier_imports_count ?? 0) }} importaciones</p>
                                    <p>{{ number_format($supplier->normalized_products_count ?? 0) }} normalizados</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3 text-[11px] font-bold">
                                    <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.show', [$mpsfpProject, $supplier]) : route('suppliers.show', $supplier) }}" class="text-primary hover:underline">VER</a>
                                    @if ($canEditSupplier)
                                        <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.edit', [$mpsfpProject, $supplier]) : route('suppliers.edit', $supplier) }}" class="text-primary hover:underline">EDITAR</a>
                                    @endif
                                    @if ($canDeleteSupplier)
                                        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.destroy', [$mpsfpProject, $supplier]) : route('suppliers.destroy', $supplier) }}" method="POST" class="inline-block" onsubmit="return confirm('Se eliminará el proveedor y todas sus importaciones asociadas. ¿Continuar?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-error hover:underline">ELIMINAR</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No hay proveedores.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer border-t border-outline-variant/10 bg-surface-container-low/20 px-6 py-4">
            <p class="text-[11px] font-bold uppercase tracking-tight text-secondary">
                Mostrando {{ $suppliers->firstItem() ?? 0 }} a {{ $suppliers->lastItem() ?? 0 }} de {{ $suppliers->total() }} proveedores
            </p>
            <div class="flex justify-center">{{ $suppliers->withQueryString()->links() }}</div>
        </div>
    </section>
</div>
@endsection
