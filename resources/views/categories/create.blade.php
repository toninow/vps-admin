@extends('layouts.app')

@section('title', 'Nueva categoría')
@section('page_title', 'Nueva categoría')

@section('content')
<div class="max-w-xl">
    <form action="{{ route('categories.store') }}" method="POST" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="parent_id" class="block text-sm font-medium text-gray-700">Categoría padre</label>
            <select name="parent_id" id="parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                <option value="">— Raíz —</option>
                @foreach ($parents as $p)
                    <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="position" class="block text-sm font-medium text-gray-700">Posición</label>
            <input type="number" name="position" id="position" value="{{ old('position', 0) }}" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        </div>
        <div>
            <label class="inline-flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                <span class="ml-2 text-sm text-gray-700">Activo</span>
            </label>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Crear categoría</button>
            <a href="{{ route('categories.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
