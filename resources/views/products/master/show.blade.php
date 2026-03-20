@extends('layouts.app')

@section('title', 'Producto maestro')
@section('page_title', 'Producto maestro')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Maestros',
            'title' => 'MPSFP / Ficha de producto maestro',
            'subtitle' => 'Estás viendo el detalle del producto consolidado en catálogo maestro.',
        ])
    @endif

    @php
        $approvalClass = $masterProduct->is_approved ? 'status-green' : 'status-gray';
        $imageCount = $masterProduct->productImages->count();
        $relatedCount = $masterProduct->normalizedProducts->count();
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Catálogo maestro</p>
                <h3 class="mt-2 text-2xl font-semibold text-[#555555]">{{ $masterProduct->name ?? 'Sin nombre' }}</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="status-badge {{ $approvalClass }}">{{ $masterProduct->is_approved ? 'Aprobado para exportación' : 'Pendiente de aprobación' }}</span>
                    <span class="status-badge status-blue">EAN: {{ $masterProduct->ean13 ?? '—' }}</span>
                    <span class="status-badge status-gray">Relacionados: {{ $relatedCount }}</span>
                    <span class="status-badge status-gray">Imágenes: {{ $imageCount }}</span>
                </div>
            </div>
            <div class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600">
                <div><span class="font-medium text-[#555555]">Referencia:</span> {{ $masterProduct->reference ?? '—' }}</div>
                <div class="mt-1"><span class="font-medium text-[#555555]">Stock operativo:</span> {{ $masterProduct->quantity ?? 0 }}</div>
                <div class="mt-1"><span class="font-medium text-[#555555]">Categoría:</span> {{ $masterProduct->category->name ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if ($imageCount > 0)
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-[#555555]">Galería del producto</h3>
                    <p class="mt-1 text-sm text-gray-500">Imágenes ya asociadas al producto maestro.</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($masterProduct->productImages->sortBy('position') as $image)
                    @php
                        $imageSrc = $image->url_original ?: (
                            \Illuminate\Support\Str::startsWith((string) $image->path_local, ['http://', 'https://', '/'])
                                ? $image->path_local
                                : asset($image->path_local)
                        );
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                        <div class="aspect-square overflow-hidden bg-white">
                            <img src="{{ $imageSrc }}" alt="Imagen producto" class="h-full w-full object-contain">
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 px-3 py-2 text-xs text-gray-500">
                            <span>#{{ $image->position }}</span>
                            @if ($image->is_cover)
                                <span class="status-badge status-pink">Portada</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->name ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">EAN13</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->ean13 ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Referencia</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->reference ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Stock operativo (quantity)</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->quantity ?? 0 }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Categoría hoja</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->category->name ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Ruta export (category_path_export)</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->formattedCategoryPath() ?: '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Aprobado para export</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $masterProduct->is_approved ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $masterProduct->is_approved ? 'Sí' : 'No' }}</span></dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Aprobado por</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->approvedBy->name ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Fecha de aprobación</dt><dd class="text-sm text-[#555555]">{{ $masterProduct->approved_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
            @can('approve', $masterProduct)
                @if ($masterProduct->is_approved)
                    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.unapprove', [$mpsfpProject, $masterProduct]) : route('products.master.unapprove', $masterProduct) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-secondary">Retirar aprobación</button>
                    </form>
                @else
                    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.approve', [$mpsfpProject, $masterProduct]) : route('products.master.approve', $masterProduct) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-primary">Aprobar para exportación</button>
                    </form>
                @endif
            @endcan
            @can('delete', $masterProduct)
                <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.destroy', [$mpsfpProject, $masterProduct]) : route('products.master.destroy', $masterProduct) }}" method="POST" onsubmit="return confirm('Se eliminará el producto maestro seleccionado. ¿Continuar?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-link-muted">Eliminar producto</button>
                </form>
            @endcan
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.master.index', $mpsfpProject) : route('products.master.index') }}" class="btn-secondary">Volver al listado</a>
        </div>
    </div>

    @if ($relatedCount > 0)
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-[#555555]">Productos normalizados relacionados</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Proveedor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">EAN</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($masterProduct->normalizedProducts->take(20) as $product)
                            <tr>
                                <td class="px-4 py-3 text-sm text-[#555555]">{{ $product->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $product->supplier->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $product->ean13 ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.normalized.show', [$mpsfpProject, $product]) : route('products.normalized.show', $product) }}" class="btn-link">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
