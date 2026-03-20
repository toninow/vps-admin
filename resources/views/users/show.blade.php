@extends('layouts.app')

@section('title', $user->name)
@section('page_title', 'Mi perfil')

@section('content')
@php
    $roleNames = $user->roles->pluck('name')->filter()->values();
    $primaryRole = $roleNames->first() ?: 'Sin rol asignado';
    $visibleProjects = $user->projects;
    $avatarUrl = $user->avatar_path
        ? asset(ltrim($user->avatar_path, '/'))
        : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=E6007E&color=ffffff&bold=true&size=256';
    $statusClass = $user->status === 'active' ? 'status-green' : 'status-gray';
    $statusLabel = $user->status === 'active' ? 'Activo' : 'Inactivo';
    $capabilityScore = min(100, 40 + ($roleNames->count() * 15) + ($visibleProjects->count() * 10));
@endphp

<div class="space-y-8">
    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="mpsfp-panel lg:col-span-2 p-8">
                <div class="flex flex-col items-center gap-6 md:flex-row md:items-start md:gap-8">
                    <div class="relative group">
                        <div class="h-32 w-32 overflow-hidden rounded-xl ring-4 ring-primary/10">
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        </div>
                    </div>

                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="font-headline text-3xl font-extrabold tracking-tight text-on-background">{{ $user->name }}</h2>
                                <p class="mt-1 text-sm font-bold uppercase tracking-wide text-primary">{{ $primaryRole }}</p>
                            </div>
                            <div class="action-stack md:justify-end">
                                @can('users.edit')
                                    <a href="{{ route('users.edit', $user) }}" class="btn-primary">Editar perfil</a>
                                @endcan
                                <a href="{{ route('users.index') }}" class="btn-secondary">Volver</a>
                            </div>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-4 border-t border-outline-variant/10 pt-6 md:grid-cols-2">
                            <div class="flex items-center gap-3 text-secondary">
                                <span class="material-symbols-outlined text-primary/60">mail</span>
                                <span class="text-sm">{{ $user->email }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-secondary">
                                <span class="material-symbols-outlined text-primary/60">verified_user</span>
                                <span class="text-sm">{{ $statusLabel }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-secondary">
                                <span class="material-symbols-outlined text-primary/60">calendar_today</span>
                                <span class="text-sm">Desde {{ $user->created_at?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-secondary">
                                <span class="material-symbols-outlined text-primary/60">login</span>
                                <span class="text-sm">Último acceso {{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-secondary md:col-span-2">
                                <span class="material-symbols-outlined text-primary/60">lan</span>
                                <span class="text-sm">Última IP {{ $user->last_login_ip ?: 'No registrada todavía' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="rounded-xl bg-surface-container-low p-8 space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-headline text-lg font-bold tracking-tight">Seguridad</h3>
                    <span class="material-symbols-outlined text-primary">security</span>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg bg-surface-container-lowest p-4">
                        <p class="mb-2 text-xs font-bold uppercase text-secondary">Contraseña</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">""""""""""""</span>
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="text-xs font-bold text-primary hover:underline">Cambiar</a>
                            @endcan
                        </div>
                    </div>

                    <div class="rounded-lg border-l-4 border-tertiary bg-surface-container-lowest p-4">
                        <p class="mb-2 text-xs font-bold uppercase text-secondary">Estado de cuenta</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="material-symbols-outlined mr-2 text-lg {{ $user->status === 'active' ? 'text-tertiary' : 'text-secondary-fixed-dim' }}" style="font-variation-settings: 'FILL' 1;">{{ $user->status === 'active' ? 'check_circle' : 'pause_circle' }}</span>
                                <span class="text-sm font-medium {{ $user->status === 'active' ? 'text-tertiary' : 'text-secondary' }}">{{ $statusLabel }}</span>
                            </div>
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-surface-container-lowest p-4">
                        <p class="mb-2 text-xs font-bold uppercase text-secondary">Roles activos</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($roleNames as $roleName)
                                <span class="mpsfp-pill status-pink">{{ $roleName }}</span>
                            @empty
                                <span class="mpsfp-pill status-gray">Sin rol asignado</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <section class="mpsfp-panel p-8 h-full">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="font-headline text-lg font-bold tracking-tight">Módulos y permisos</h3>
                <div class="rounded bg-primary/10 px-3 py-1 text-[0.68rem] font-bold uppercase tracking-wider text-primary">
                    Rol: {{ $primaryRole }}
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($roleNames as $roleName)
                    <div class="group flex items-center justify-between rounded bg-surface-container-low p-3 transition-colors hover:bg-surface-container-high">
                        <div class="flex items-center space-x-3">
                            <span class="material-symbols-outlined text-lg transition-colors group-hover:text-primary">verified_user</span>
                            <span class="text-sm font-medium">{{ $roleName }}</span>
                        </div>
                        <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings: 'FILL' 1;">verified</span>
                    </div>
                @empty
                    <div class="rounded bg-surface-container-low p-3 text-sm text-secondary">
                        Este usuario todavía no tiene roles asignados.
                    </div>
                @endforelse

                <div class="group flex items-center justify-between rounded bg-surface-container-low p-3 transition-colors hover:bg-surface-container-high">
                    <div class="flex items-center space-x-3">
                        <span class="material-symbols-outlined text-lg transition-colors group-hover:text-primary">account_tree</span>
                        <span class="text-sm font-medium">Proyectos asignados</span>
                    </div>
                    <span class="text-sm font-bold text-primary">{{ $visibleProjects->count() }}</span>
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-outline-variant/10 bg-surface-container-low p-4">
                <p class="mb-2 text-[0.68rem] font-bold uppercase text-secondary">Resumen de capacidad</p>
                <div class="flex items-center justify-between text-xs font-medium">
                    <span>Acceso operativo</span>
                    <span class="text-primary">{{ $capabilityScore }}%</span>
                </div>
                <div class="mt-2 h-1 w-full rounded-full bg-surface-container-high">
                    <div class="h-1 rounded-full bg-primary" style="width: {{ $capabilityScore }}%"></div>
                </div>
            </div>
        </section>

        <section class="mpsfp-panel p-8 lg:col-span-2">
            <div class="mb-8 flex items-center justify-between">
                <h3 class="font-headline text-lg font-bold tracking-tight">Actividad del sistema</h3>
                @can('logs.view')
                    <a href="{{ route('activity-logs.index', ['user_id' => $user->id]) }}" class="text-primary text-xs font-bold flex items-center hover:opacity-70">
                        Ver todo el historial <span class="material-symbols-outlined ml-1 text-sm">arrow_forward</span>
                    </a>
                @endcan
            </div>

            @if ($recentActivity->isEmpty())
                <div class="rounded-xl bg-surface-container-low p-6 text-sm text-secondary">
                    No hay actividad registrada todavía para este usuario.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/10">
                                <th class="pb-4 text-[0.68rem] font-bold uppercase tracking-widest text-secondary">Acción</th>
                                <th class="pb-4 text-[0.68rem] font-bold uppercase tracking-widest text-secondary">Detalle</th>
                                <th class="pb-4 text-[0.68rem] font-bold uppercase tracking-widest text-secondary">Fecha</th>
                                <th class="pb-4 text-right text-[0.68rem] font-bold uppercase tracking-widest text-secondary">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/5">
                            @foreach ($recentActivity as $activity)
                                @php
                                    $isError = str_contains(strtolower((string) $activity->action), 'error') || str_contains(strtolower((string) $activity->description), 'error');
                                @endphp
                                <tr class="group transition-colors hover:bg-surface-container-low">
                                    <td class="py-4">
                                        <div class="flex items-center">
                                            <span class="material-symbols-outlined mr-3 text-lg text-primary">{{ $isError ? 'warning' : 'task_alt' }}</span>
                                            <span class="text-sm font-semibold text-on-background">{{ $activity->action }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <span class="rounded bg-surface-container-high px-2 py-1 text-xs font-medium text-secondary">
                                            {{ \Illuminate\Support\Str::limit($activity->description ?: 'Sin detalle', 60) }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-xs text-secondary">{{ $activity->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="py-4 text-right">
                                        <span class="rounded px-2 py-0.5 text-[0.68rem] font-bold {{ $isError ? 'bg-error-container text-on-error-container' : 'bg-tertiary-container text-on-tertiary' }}">
                                            {{ $isError ? 'ERROR' : 'OK' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($visibleProjects->isNotEmpty())
                <div class="mt-8 rounded-xl bg-surface-container-low p-6">
                    <div class="compact-toolbar gap-3">
                        <div>
                            <h4 class="font-headline text-base font-bold text-on-background">Proyectos asignados</h4>
                            <p class="mt-1 text-sm text-secondary">Acceso operativo actual dentro del sistema.</p>
                        </div>
                        <span class="mpsfp-pill status-blue">{{ $visibleProjects->count() }} proyectos</span>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach ($visibleProjects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="group rounded-lg bg-white p-4 transition-colors hover:bg-surface-container-high">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-on-background">{{ $project->name }}</p>
                                        <p class="mt-1 text-xs text-secondary">Nivel: {{ $project->pivot->access_level }}</p>
                                    </div>
                                    <span class="material-symbols-outlined text-secondary transition-colors group-hover:text-primary">arrow_forward</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
