@extends('layouts.app')

@section('title', 'Producto normalizado')
@section('page_title', 'Producto normalizado')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Normalizados',
            'title' => 'MPSFP / Ficha de producto normalizado',
            'subtitle' => 'Detalle técnico y comercial del producto generado a partir del archivo del proveedor.',
        ])
    @endif

    @include('products.normalized._detail_panel', [
        'normalizedProduct' => $normalizedProduct,
        'imageCandidates' => $imageCandidates ?? [],
        'storedImageUrls' => $storedImageUrls ?? [],
        'rawImageUrls' => $rawImageUrls ?? [],
        'mpsfpProject' => $mpsfpProject ?? null,
    ])

    <div class="flex flex-wrap gap-2">
        @can('delete', $normalizedProduct)
            <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.normalized.destroy', [$mpsfpProject, $normalizedProduct]) : route('products.normalized.destroy', $normalizedProduct) }}" method="POST" onsubmit="return confirm('Se eliminará el producto normalizado seleccionado. ¿Continuar?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-link-muted">Eliminar producto</button>
            </form>
        @endcan
        <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.normalized.index', $mpsfpProject) : route('products.normalized.index') }}" class="btn-secondary">Volver al listado</a>
    </div>
</div>
@endsection

@include('products.normalized._gallery_script')
