@extends('layouts.app')

@section('title', 'Editar proveedor')
@section('page_title', 'Editar proveedor')

@section('content')
<div class="max-w-xl">
    @if (isset($mpsfpProject))
        <div class="mb-4">
            @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        </div>
        <div class="mb-4">
            @include('projects.mpsfp._context', [
                'project' => $mpsfpProject,
                'label' => 'Proveedores',
                'title' => 'MPSFP / Editar proveedor',
                'subtitle' => 'Estás modificando la ficha del proveedor ' . $supplier->name . '.',
            ])
        </div>
    @endif

    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.update', [$mpsfpProject, $supplier]) : route('suppliers.update', $supplier) }}" method="POST" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" value="{{ old('name', $supplier->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $supplier->slug) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">Código</label>
            <input type="text" name="code" id="code" value="{{ old('code', $supplier->code) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="inline-flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                <span class="ml-2 text-sm text-gray-700">Activo</span>
            </label>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Guardar</button>
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.show', [$mpsfpProject, $supplier]) : route('suppliers.show', $supplier) }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
