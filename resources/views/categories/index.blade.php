@extends('layouts.app')

@section('title', 'Categorías')
@section('page_title', 'Categorías')

@section('content')
<div class="space-y-4">
    @can('categories.create')
        <div class="flex justify-end">
            <a href="{{ route('categories.create') }}" class="btn-primary">Nueva categoría</a>
        </div>
    @endcan

    <form method="GET" action="{{ route('categories.index') }}" class="flex flex-wrap gap-2">
        <button type="submit" class="btn-secondary">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Slug</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Padre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Posición</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 text-sm text-[#555555]">{{ $category->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $category->slug }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $category->parent->name ?? '— (raíz)' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $category->position }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('categories.show', $category) }}" class="btn-link">Ver</a>
                            @can('categories.edit')
                                <a href="{{ route('categories.edit', $category) }}" class="btn-link-muted ml-2">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay categorías.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">{{ $categories->withQueryString()->links() }}</div>
</div>
@endsection
