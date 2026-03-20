@extends('layouts.app')

@section('title', 'Grupo duplicados')
@section('page_title', 'Grupo duplicados')

@section('content')
<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">EAN13</dt><dd class="text-sm text-[#555555]">{{ $duplicate->ean13 }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $duplicate->status ?? 'pending' }}</span></dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Producto maestro</dt><dd class="text-sm">@if ($duplicate->masterProduct)<a href="{{ route('products.master.show', $duplicate->masterProduct) }}" class="btn-link">{{ $duplicate->masterProduct->name ?? $duplicate->masterProduct->id }}</a>@else — @endif</dd></div>
        </dl>
        <h3 class="mt-4 text-sm font-semibold text-[#555555]">Items del grupo</h3>
        <ul class="mt-2 space-y-1 text-sm text-gray-600">
            @foreach ($duplicate->duplicateProductGroupItems as $item)
                <li>
                    @if ($item->normalizedProduct)
                        <a href="{{ route('products.normalized.show', $item->normalizedProduct) }}" class="btn-link">{{ $item->normalizedProduct->name ?? $item->normalizedProduct->id }}</a>
                    @else
                        —
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">
            <a href="{{ route('duplicates.index') }}" class="btn-secondary">Volver al listado</a>
        </div>
    </div>
</div>
@endsection
