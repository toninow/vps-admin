@extends('layouts.app')

@section('title', 'MPSFP · ' . $sectionMeta['label'])
@section('page_title', 'MPSFP · ' . $sectionMeta['label'])

@section('content')
<div class="space-y-6">
    @include('projects.mpsfp._nav', ['project' => $project, 'sections' => $sections, 'section' => $section])
    @include('projects.mpsfp._context', [
        'project' => $project,
        'label' => $sectionMeta['label'],
        'title' => 'MPSFP / ' . $sectionMeta['label'],
        'subtitle' => $sectionMeta['description'],
    ])

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-[#555555]">{{ $sectionMeta['label'] }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $sectionMeta['mode'] === 'manage' ? 'bg-[#E6007E]/10 text-[#E6007E]' : 'bg-blue-50 text-blue-700' }}">
                        {{ $sectionMeta['mode_label'] }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $sectionMeta['description'] }}</p>
            </div>
            <div class="action-stack">
                <a href="{{ $sectionData['primary_url'] }}" class="btn-primary">{{ $sectionData['primary_label'] }}</a>
                @foreach ($sectionData['secondary_actions'] as $action)
                    <a href="{{ $action['url'] }}" class="btn-secondary">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-500">{{ $sectionMeta['reason'] }}</p>
    </div>

    @if (! empty($sectionData['summary']))
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($sectionData['summary'] as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm card-hover">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $item['label'] }}</p>
                    <p class="mt-1 text-3xl font-bold text-[#555555]">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-sm font-semibold text-[#555555]">Vista rápida</h3>
            <a href="{{ $sectionData['primary_url'] }}" class="btn-link">Abrir gestión completa</a>
        </div>

        @if ($sectionData['table'] && ! empty($sectionData['table']['rows']))
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($sectionData['table']['columns'] as $column)
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($sectionData['table']['rows'] as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        @if (is_array($cell) && isset($cell['url'], $cell['label']))
                                            <a href="{{ $cell['url'] }}" class="btn-link">{{ $cell['label'] }}</a>
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-4 text-sm text-gray-500">{{ $sectionData['table']['empty'] ?? 'No hay datos disponibles.' }}</p>
        @endif
    </div>

    <a href="{{ route('projects.show', $project) }}" class="inline-block text-sm text-[#555555] hover:underline">← Volver al proyecto</a>
</div>
@endsection
