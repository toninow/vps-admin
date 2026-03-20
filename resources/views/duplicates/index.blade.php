@extends('layouts.app')

@section('title', 'Duplicados por EAN')
@section('page_title', 'Duplicados por EAN')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('duplicates.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <select name="status" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
            <option value="merged" {{ request('status') === 'merged' ? 'selected' : '' }}>Fusionado</option>
        </select>
        <button type="submit" class="btn-secondary">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">EAN13</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Productos en grupo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Maestro asignado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($groups as $group)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[#555555]">{{ $group->ean13 }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $group->duplicateProductGroupItems->count() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ $group->masterProduct->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $group->status ?? 'pending' }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('duplicates.show', $group) }}" class="btn-link">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay grupos de duplicados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">{{ $groups->withQueryString()->links() }}</div>
</div>
@endsection
