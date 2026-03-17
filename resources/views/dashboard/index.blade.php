@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats cards (enlazan a la sección correspondiente) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php $cardClass = 'card-hover animate-fade-in rounded-2xl border border-gray-200/80 bg-white p-5 shadow-md block transition no-underline text-[#555555] hover:text-[#555555]'; @endphp
        @can('users.view')
            <a href="{{ route('users.index') }}" class="{{ $cardClass }}" style="animation-delay: 0.05s">
        @else
            <div class="{{ $cardClass }}" style="animation-delay: 0.05s">
        @endcan
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Usuarios</p>
                    <p class="mt-1 text-3xl font-bold text-[#555555]">{{ $totalUsers }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">{{ $activeUsers }} activos</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#E6007E]/10 text-[#E6007E]">
                    <span class="text-2xl" aria-hidden="true">👥</span>
                </div>
            </div>
        @can('users.view')</a>@else</div>@endcan

        @can('projects.view')
            <a href="{{ route('projects.index') }}" class="{{ $cardClass }}" style="animation-delay: 0.1s">
        @else
            <div class="{{ $cardClass }}" style="animation-delay: 0.1s">
        @endcan
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Proyectos</p>
                    <p class="mt-1 text-3xl font-bold text-[#555555]">{{ $totalProjects }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">{{ $activeProjects }} activos</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600">
                    <span class="text-2xl" aria-hidden="true">📁</span>
                </div>
            </div>
        @can('projects.view')</a>@else</div>@endcan

        @can('logs.view')
            <a href="{{ route('activity-logs.index') }}" class="{{ $cardClass }}" style="animation-delay: 0.15s">
        @else
            <div class="{{ $cardClass }}" style="animation-delay: 0.15s">
        @endcan
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Accesos</p>
                    <p class="mt-1 text-3xl font-bold text-[#555555]">{{ $recentLogins->count() }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">Últimos logins</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-500/10 text-green-600">
                    <span class="text-2xl" aria-hidden="true">🔐</span>
                </div>
            </div>
        @can('logs.view')</a>@else</div>@endcan

        @can('logs.view')
            <a href="{{ route('activity-logs.index') }}" class="{{ $cardClass }}" style="animation-delay: 0.2s">
        @else
            <div class="{{ $cardClass }}" style="animation-delay: 0.2s">
        @endcan
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Actividad</p>
                    <p class="mt-1 text-3xl font-bold text-[#555555]">{{ $recentActivities->count() }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">Últimas acciones</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                    <span class="text-2xl" aria-hidden="true">📋</span>
                </div>
            </div>
        @can('logs.view')</a>@else</div>@endcan
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Proyectos -->
        <div class="animate-slide-up lg:col-span-2 rounded-2xl border border-gray-200/80 bg-white p-5 shadow-md" style="animation-delay: 0.25s">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-[#555555]">Proyectos</h2>
                @can('projects.view')
                    <a href="{{ route('projects.index') }}" class="text-sm font-medium text-[#E6007E] hover:underline">Ver todos →</a>
                @endcan
            </div>
            @if ($projects->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 py-8 text-center text-sm text-gray-500">
                    No hay proyectos asignados. Crea uno desde Proyectos.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($projects as $index => $project)
                        <a href="{{ route('projects.show', $project) }}" class="animate-slide-up card-hover flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 transition" style="animation-delay: {{ 0.3 + $index * 0.05 }}s">
                            <div class="flex items-center gap-3">
                                @if ($project->color)
                                    <span class="h-4 w-4 rounded-full flex-shrink-0 shadow-sm" style="background-color: {{ $project->color }}"></span>
                                @else
                                    <span class="h-4 w-4 rounded-full bg-gray-300 flex-shrink-0"></span>
                                @endif
                                <span class="font-medium text-[#555555]">{{ $project->name }}</span>
                                <span class="rounded-lg bg-gray-200/80 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $project->status }}</span>
                            </div>
                            <span class="rounded-lg bg-[#E6007E]/10 px-3 py-1.5 text-sm font-medium text-[#E6007E]">Ver →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Actividad reciente -->
        <div class="animate-slide-up rounded-2xl border border-gray-200/80 bg-white p-5 shadow-md" style="animation-delay: 0.3s">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-[#555555]">Actividad reciente</h2>
                @can('logs.view')
                    <a href="{{ route('activity-logs.index') }}" class="text-sm font-medium text-[#E6007E] hover:underline">Logs →</a>
                @endcan
            </div>
            @if ($recentActivities->isEmpty())
                <p class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 py-6 text-center text-sm text-gray-500">Sin actividad reciente.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($recentActivities as $log)
                        <li class="animate-fade-in rounded-xl border border-gray-100 bg-gray-50/50 px-3 py-2.5 text-sm transition hover:bg-gray-50">
                            <span class="font-medium text-[#555555]">{{ $log->user?->name ?? 'Sistema' }}</span>
                            <span class="text-gray-500">— {{ Str::limit($log->description ?? $log->action, 40) }}</span>
                            <div class="mt-0.5 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
