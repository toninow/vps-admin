@extends('layouts.app')

@section('title', 'Integraciones móviles')
@section('page_title', 'Integraciones móviles')

@section('content')
<div class="space-y-4">
    @can('mobile_integrations.edit')
        <div class="flex justify-end">
            <a href="{{ route('mobile-integrations.create') }}" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Nueva integración</a>
        </div>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">App</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Proyecto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Plataforma</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Versión</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($integrations as $mi)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-[#555555]">{{ $mi->app_name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600"><a href="{{ route('projects.show', $mi->project) }}" class="text-[#E6007E] hover:underline">{{ $mi->project->name }}</a></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $mi->platform }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $mi->current_version }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">{{ $mi->connection_status }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('mobile-integrations.show', $mi) }}" class="btn-link">Ver</a>
                            @can('mobile_integrations.edit')
                                <a href="{{ route('mobile-integrations.edit', $mi) }}" class="btn-link-muted ml-2">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay integraciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-center">{{ $integrations->withQueryString()->links() }}</div>
</div>
@endsection
