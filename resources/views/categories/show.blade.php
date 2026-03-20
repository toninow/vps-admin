@extends('layouts.app')

@section('title', 'Categoría: ' . $category->name)
@section('page_title', 'Categoría: ' . $category->name)

@section('content')
<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="text-sm text-[#555555]">{{ $category->name }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Slug</dt><dd class="text-sm text-[#555555]">{{ $category->slug }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Padre</dt><dd class="text-sm text-[#555555]">{{ $category->parent->name ?? '— (raíz)' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Posición</dt><dd class="text-sm text-[#555555]">{{ $category->position }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</span></dd></div>
        </dl>
        @can('categories.edit')
            <div class="mt-4">
                <a href="{{ route('categories.edit', $category) }}" class="btn-secondary">Editar</a>
            </div>
        @endcan
    </div>
</div>
@endsection
