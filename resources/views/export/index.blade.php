@extends('layouts.app')

@section('title', 'Exportar PrestaShop')
@section('page_title', 'Exportar PrestaShop')

@section('content')
<div class="space-y-4">
@if (isset($mpsfpProject))
    @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
    @include('projects.mpsfp._context', [
        'project' => $mpsfpProject,
        'label' => 'Exportación',
        'title' => 'MPSFP / Exportación',
        'subtitle' => 'Último paso: revisar maestros aprobados y descargar el CSV con columnas de precio compatibles con PrestaShop 1.7.8.11.',
    ])
@endif

@php
    $stats = $exportStats ?? [
        'approved_total' => 0,
        'preview_count' => 0,
        'preview_ready' => 0,
        'preview_warnings' => 0,
        'default_tax_rate' => 21,
        'default_tax_rule_id' => 1,
    ];
    $previewRows = $exportPreviewRows ?? collect();
    $downloadRoute = isset($mpsfpProject)
        ? route('projects.mpsfp.export.download', $mpsfpProject, false)
        : route('export.download', [], false);
    $indexRoute = isset($mpsfpProject)
        ? route('projects.mpsfp.export.index', $mpsfpProject, false)
        : route('export.index', [], false);
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Aprobados</p>
        <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['approved_total'] ?? 0) }}</p>
        <p class="mt-2 text-sm text-slate-500">Maestros listos para exportación final.</p>
    </div>
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Preview lista</p>
        <p class="mt-2 text-3xl font-black text-emerald-900">{{ number_format($stats['preview_ready'] ?? 0) }}</p>
        <p class="mt-2 text-sm text-emerald-800">Muestras sin alertas de precio ni impuestos.</p>
    </div>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Con aviso</p>
        <p class="mt-2 text-3xl font-black text-amber-900">{{ number_format($stats['preview_warnings'] ?? 0) }}</p>
        <p class="mt-2 text-sm text-amber-800">Filas del preview que conviene revisar antes de descargar.</p>
    </div>
    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Fiscalidad</p>
        <p class="mt-2 text-3xl font-black text-sky-900">{{ rtrim(rtrim(number_format((float) ($stats['default_tax_rate'] ?? 21), 2, '.', ''), '0'), '.') }}%</p>
        <p class="mt-2 text-sm text-sky-800">`Tax rules ID {{ $stats['default_tax_rule_id'] ?? 1 }}` para calcular `Price tax excluded` y `Price tax included`.</p>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-3xl space-y-2">
            <p class="text-sm text-slate-600">El CSV sale con separador `;` y decimales con punto a 6 posiciones, que es el formato más estable para importarlo en PrestaShop 1.7.8.11 sin romper `Wholesale price`, `Price tax excluded` y `Price tax included`.</p>
            <p class="text-sm text-slate-500">La venta se exporta en ambas columnas fiscales y el coste sale como `Wholesale price`. Si el origen era ambiguo, la fila queda marcada con aviso en esta preview.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="GET" action="{{ $indexRoute }}" class="flex flex-col gap-3 sm:flex-row">
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700">
                    <input type="checkbox" name="approved_only" value="1" {{ request()->boolean('approved_only', true) ? 'checked' : '' }}>
                    <span>Solo aprobados</span>
                </label>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, referencia o EAN" class="w-full min-w-[260px] rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100">
                <button type="submit" class="btn-secondary">Actualizar preview</button>
            </form>
            @can('export.download')
                <a href="{{ $downloadRoute . '?' . http_build_query(request()->query()) }}" class="btn-primary whitespace-nowrap">Descargar CSV PrestaShop</a>
            @endcan
        </div>
    </div>
    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <p class="font-semibold text-slate-800">Columnas de precio generadas</p>
        <p class="mt-2">`Wholesale price` = coste sin IVA · `Price tax excluded` = venta sin IVA · `Price tax included` = venta con IVA · `Tax rules ID` = regla fiscal que PrestaShop usará al importar.</p>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-bold text-slate-900">Preview de exportación</h2>
        <p class="mt-1 text-sm text-slate-500">Se muestran las primeras {{ number_format($stats['preview_count'] ?? 0) }} filas de la selección actual.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Producto</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Proveedor origen</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Wholesale price</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Price tax excluded</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Price tax included</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Avisos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($previewRows as $entry)
                    @php
                        $product = $entry['masterProduct'];
                        $row = $entry['row'];
                        $warnings = $entry['warnings'];
                        $source = $entry['source'];
                    @endphp
                    <tr class="align-top">
                        <td class="px-4 py-4">
                            <div class="font-semibold text-slate-900">{{ $product->name ?: 'Sin nombre' }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                Ref: {{ $row['Reference'] !== '' ? $row['Reference'] : '—' }} ·
                                EAN: {{ $row['EAN13'] !== '' ? $row['EAN13'] : '—' }} ·
                                Tax rule: {{ $row['Tax rules ID'] !== '' ? $row['Tax rules ID'] : '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ $row['Categories'] !== '' ? $row['Categories'] : 'Sin ruta de categoría' }}</div>
                        </td>
                        <td class="px-4 py-4 text-slate-600">
                            <div>{{ $source['supplier'] ?: '—' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $source['import_filename'] ?: 'Sin importación asociada' }}</div>
                        </td>
                        <td class="px-4 py-4 font-mono text-slate-700">{{ $row['Wholesale price'] }}</td>
                        <td class="px-4 py-4 font-mono text-slate-700">{{ $row['Price tax excluded'] }}</td>
                        <td class="px-4 py-4 font-mono text-slate-700">{{ $row['Price tax included'] }}</td>
                        <td class="px-4 py-4">
                            @if ($warnings === [])
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Lista para exportar</span>
                            @else
                                <div class="space-y-2">
                                    @foreach ($warnings as $warning)
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">{{ $warning }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay productos maestros para esta selección.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
