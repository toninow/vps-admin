@extends('layouts.app')

@section('title', 'Logs de actividad')
@section('page_title', 'Logs de actividad')

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('activity-logs.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="action" value="{{ request('action') }}" placeholder="Acción" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 shadow-sm sm:text-sm">
        <button type="submit" class="rounded-md bg-gray-200 px-3 py-2 text-sm font-medium text-[#555555] hover:bg-gray-300">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Acción</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Descripción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[#555555]">{{ $log->user?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ Str::limit($log->description, 80) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-center">{{ $logs->withQueryString()->links() }}</div>
</div>
@endsection
