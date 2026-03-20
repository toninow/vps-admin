@extends('layouts.app')

@section('title', 'Usuarios')
@section('page_title', 'Usuarios')

@section('content')
<div class="space-y-4">
    @can('users.create')
        <div class="flex justify-end">
            <a href="{{ route('users.create') }}" class="btn-primary w-full sm:w-auto">Nuevo usuario</a>
        </div>
    @endcan

    <form method="GET" action="{{ route('users.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        <select name="status" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos los estados</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
        <button type="submit" class="btn-secondary">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Rol</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Último acceso</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($users as $user)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[#555555]">{{ $user->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $user->status }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            @can('users.view')
                                <a href="{{ route('users.show', $user) }}" class="btn-link">Ver</a>
                            @endcan
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="btn-link-muted ml-2">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">{{ $users->withQueryString()->links() }}</div>
</div>
@endsection
