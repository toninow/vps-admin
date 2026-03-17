@extends('layouts.app')

@section('title', $user->name)
@section('page_title', $user->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="text-sm text-[#555555]">{{ $user->name }}</dd>
            <dt class="text-sm font-medium text-gray-500">Email</dt><dd class="text-sm text-[#555555]">{{ $user->email }}</dd>
            <dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $user->status }}</span></dd>
            <dt class="text-sm font-medium text-gray-500">Roles</dt><dd class="text-sm text-[#555555]">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd>
            <dt class="text-sm font-medium text-gray-500">Último acceso</dt><dd class="text-sm text-[#555555]">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </dl>
        <div class="mt-4 flex gap-2">
            @can('users.edit')<a href="{{ route('users.edit', $user) }}" class="btn-primary text-sm py-2 px-3">Editar</a>@endcan
            <a href="{{ route('users.index') }}" class="btn-secondary text-sm py-2 px-3">Volver</a>
        </div>
    </div>
    @if ($user->projects->isNotEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-[#555555] mb-2">Proyectos asignados</h2>
            <ul class="space-y-1 text-sm">
                @foreach ($user->projects as $p)
                    <li><a href="{{ route('projects.show', $p) }}" class="text-[#E6007E] hover:underline">{{ $p->name }}</a> ({{ $p->pivot->access_level }})</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
