@extends('layouts.app')

@section('title', 'Editar proyecto')
@section('page_title', 'Editar proyecto')

@section('content')
<form action="{{ route('projects.update', $project) }}" method="POST" class="mx-auto max-w-2xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm" id="users">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-[#555555]">Nombre</label>
            <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-[#555555]">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $project->slug) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-[#555555]">Estado</label>
            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                <option value="active" {{ old('status', $project->status) === 'active' ? 'selected' : '' }}>Activo</option>
                <option value="development" {{ old('status', $project->status) === 'development' ? 'selected' : '' }}>Desarrollo</option>
                <option value="paused" {{ old('status', $project->status) === 'paused' ? 'selected' : '' }}>Pausado</option>
                <option value="archived" {{ old('status', $project->status) === 'archived' ? 'selected' : '' }}>Archivado</option>
            </select>
        </div>
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-[#555555]">Descripción</label>
        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">{{ old('description', $project->description) }}</textarea>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><label for="public_url" class="block text-sm font-medium text-[#555555]">URL pública</label><input type="url" name="public_url" id="public_url" value="{{ old('public_url', $project->public_url) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></div>
        <div><label for="admin_url" class="block text-sm font-medium text-[#555555]">URL admin</label><input type="url" name="admin_url" id="admin_url" value="{{ old('admin_url', $project->admin_url) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></div>
        <div><label for="project_type" class="block text-sm font-medium text-[#555555]">Tipo</label><input type="text" name="project_type" id="project_type" value="{{ old('project_type', $project->project_type) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></div>
        <div><label for="framework" class="block text-sm font-medium text-[#555555]">Framework</label><input type="text" name="framework" id="framework" value="{{ old('framework', $project->framework) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></div>
        <div><label for="color" class="block text-sm font-medium text-[#555555]">Color</label><input type="text" name="color" id="color" value="{{ old('color', $project->color) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></div>
        <div class="flex items-end gap-4">
            <label class="inline-flex items-center"><input type="hidden" name="has_api" value="0"><input type="checkbox" name="has_api" value="1" {{ old('has_api', $project->has_api) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]"><span class="ml-2 text-sm text-[#555555]">Tiene API</span></label>
            <label class="inline-flex items-center"><input type="hidden" name="has_mobile_app" value="0"><input type="checkbox" name="has_mobile_app" value="1" {{ old('has_mobile_app', $project->has_mobile_app) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]"><span class="ml-2 text-sm text-[#555555]">App móvil</span></label>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-[#555555] mb-2">Usuarios asignados</label>
        <p class="text-xs text-gray-500 mb-2">Selecciona usuarios y nivel de acceso. Los no seleccionados se quitarán del proyecto.</p>
        <div class="space-y-2 rounded border border-gray-200 p-3">
            @foreach ($users as $u)
                @php $pivot = $project->users->firstWhere('id', $u->id); @endphp
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" {{ $project->users->contains($u) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                    <span class="flex-1 text-sm text-[#555555]">{{ $u->name }} ({{ $u->email }})</span>
                    <select name="access_levels[{{ $u->id }}]" class="rounded border-gray-300 text-sm">
                        <option value="viewer" {{ ($pivot?->pivot->access_level ?? 'viewer') === 'viewer' ? 'selected' : '' }}>viewer</option>
                        <option value="editor" {{ ($pivot?->pivot->access_level ?? '') === 'editor' ? 'selected' : '' }}>editor</option>
                        <option value="admin" {{ ($pivot?->pivot->access_level ?? '') === 'admin' ? 'selected' : '' }}>admin</option>
                        <option value="owner" {{ ($pivot?->pivot->access_level ?? '') === 'owner' ? 'selected' : '' }}>owner</option>
                    </select>
                </label>
            @endforeach
        </div>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Guardar</button>
        <a href="{{ route('projects.show', $project) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-[#555555] hover:bg-gray-50">Cancelar</a>
    </div>
</form>
@endsection
