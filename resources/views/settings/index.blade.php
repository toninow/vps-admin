@extends('layouts.app')

@section('title', 'Configuración')
@section('page_title', 'Configuración')

@section('content')
<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Clave</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Valor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Tipo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($settings as $setting)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-[#555555]">{{ $setting->key }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $setting->value }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $setting->type ?? 'string' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No hay configuración.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
