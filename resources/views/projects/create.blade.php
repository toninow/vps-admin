@extends('layouts.app')

@section('title', 'Nuevo proyecto')
@section('page_title', 'Nuevo proyecto')

@section('content')
<form action="{{ route('projects.store') }}" method="POST" class="mx-auto max-w-2xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-[#555555]">Nombre</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-[#555555]">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="proyecto-ejemplo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-[#555555]">Estado</label>
            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activo</option>
                <option value="development" {{ old('status', 'development') === 'development' ? 'selected' : '' }}>Desarrollo</option>
                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Pausado</option>
                <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
            </select>
        </div>
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-[#555555]">Descripción</label>
        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">{{ old('description') }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="public_url" class="block text-sm font-medium text-[#555555]">URL pública</label>
            <input type="url" name="public_url" id="public_url" value="{{ old('public_url') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="admin_url" class="block text-sm font-medium text-[#555555]">URL admin</label>
            <input type="url" name="admin_url" id="admin_url" value="{{ old('admin_url') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="project_type" class="block text-sm font-medium text-[#555555]">Tipo</label>
            <input type="text" name="project_type" id="project_type" value="{{ old('project_type') }}" placeholder="web, api, móvil..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="framework" class="block text-sm font-medium text-[#555555]">Framework</label>
            <input type="text" name="framework" id="framework" value="{{ old('framework') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="color" class="block text-sm font-medium text-[#555555]">Color (hex)</label>
            <input type="text" name="color" id="color" value="{{ old('color', '#E6007E') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div class="flex items-end gap-4">
            <label class="inline-flex items-center">
                <input type="hidden" name="has_api" value="0">
                <input type="checkbox" name="has_api" value="1" {{ old('has_api') ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                <span class="ml-2 text-sm text-[#555555]">Tiene API</span>
            </label>
            <label class="inline-flex items-center">
                <input type="hidden" name="has_mobile_app" value="0">
                <input type="checkbox" name="has_mobile_app" value="1" {{ old('has_mobile_app') ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                <span class="ml-2 text-sm text-[#555555]">App móvil</span>
            </label>
        </div>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Crear</button>
        <a href="{{ route('projects.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-[#555555] hover:bg-gray-50">Cancelar</a>
    </div>
</form>
@endsection
