@extends('layouts.app')

@section('title', 'Nueva integración móvil')
@section('page_title', 'Nueva integración móvil')

@section('content')
<form action="{{ route('mobile-integrations.store') }}" method="POST" class="mx-auto max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div>
        <label for="project_id" class="block text-sm font-medium text-[#555555]">Proyecto</label>
        <select name="project_id" id="project_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            <option value="">Seleccionar</option>
            @foreach ($projects as $p)
                <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        @error('project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="app_name" class="block text-sm font-medium text-[#555555]">Nombre de la app</label>
        <input type="text" name="app_name" id="app_name" value="{{ old('app_name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        @error('app_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="platform" class="block text-sm font-medium text-[#555555]">Plataforma</label>
        <select name="platform" id="platform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="android" {{ old('platform') === 'android' ? 'selected' : '' }}>Android</option>
            <option value="ios" {{ old('platform') === 'ios' ? 'selected' : '' }}>iOS</option>
            <option value="flutter" {{ old('platform') === 'flutter' ? 'selected' : '' }}>Flutter</option>
            <option value="react_native" {{ old('platform') === 'react_native' ? 'selected' : '' }}>React Native</option>
            <option value="other" {{ old('platform', 'other') === 'other' ? 'selected' : '' }}>Otro</option>
        </select>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="current_version" class="block text-sm font-medium text-[#555555]">Versión actual</label>
            <input type="text" name="current_version" id="current_version" value="{{ old('current_version') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="min_supported_version" class="block text-sm font-medium text-[#555555]">Versión mínima</label>
            <input type="text" name="min_supported_version" id="min_supported_version" value="{{ old('min_supported_version') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
    </div>
    <div>
        <label for="api_url" class="block text-sm font-medium text-[#555555]">URL API</label>
        <input type="url" name="api_url" id="api_url" value="{{ old('api_url') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
    </div>
    <div>
        <label for="integration_token" class="block text-sm font-medium text-[#555555]">Token (se guarda encriptado)</label>
        <input type="password" name="integration_token" id="integration_token" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" autocomplete="off">
    </div>
    <div>
        <label for="connection_status" class="block text-sm font-medium text-[#555555]">Estado conexión</label>
        <select name="connection_status" id="connection_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="unknown" {{ old('connection_status', 'unknown') === 'unknown' ? 'selected' : '' }}>Desconocido</option>
            <option value="online" {{ old('connection_status') === 'online' ? 'selected' : '' }}>Online</option>
            <option value="offline" {{ old('connection_status') === 'offline' ? 'selected' : '' }}>Offline</option>
            <option value="degraded" {{ old('connection_status') === 'degraded' ? 'selected' : '' }}>Degradado</option>
        </select>
    </div>
    <div>
        <label for="notes" class="block text-sm font-medium text-[#555555]">Notas</label>
        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">{{ old('notes') }}</textarea>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Crear</button>
        <a href="{{ route('mobile-integrations.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-[#555555] hover:bg-gray-50">Cancelar</a>
    </div>
</form>
@endsection
