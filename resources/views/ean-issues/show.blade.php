@extends('layouts.app')

@section('title', 'Incidencia EAN')
@section('page_title', 'Incidencia EAN')

@section('content')
<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">Tipo</dt><dd class="text-sm text-[#555555]">{{ $eanIssue->issue_type }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Valor recibido</dt><dd class="text-sm text-[#555555]">{{ $eanIssue->value_received ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Resuelto</dt><dd class="text-sm text-[#555555]">{{ $eanIssue->resolved_at ? $eanIssue->resolved_at->format('d/m/Y H:i') : 'No' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Resuelto por</dt><dd class="text-sm text-[#555555]">{{ $eanIssue->resolvedBy->name ?? '—' }}</dd></div>
            @if ($eanIssue->resolution_notes)
                <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">Notas</dt><dd class="text-sm text-[#555555]">{{ $eanIssue->resolution_notes }}</dd></div>
            @endif
        </dl>
        <div class="mt-4">
            <a href="{{ route('ean-issues.index') }}" class="btn-secondary">Volver al listado</a>
        </div>
    </div>
</div>
@endsection
