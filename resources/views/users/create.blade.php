@extends('layouts.app')

@section('title', 'Nuevo usuario')
@section('page_title', 'Nuevo usuario')

@section('content')
<form action="{{ route('users.store') }}" method="POST" class="mx-auto max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div>
        <label for="name" class="block text-sm font-medium text-[#555555]">Nombre</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-[#555555]">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-[#555555]">Contraseña</label>
        <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-[#555555]">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-[#555555]">Estado</label>
        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Activo</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-[#555555] mb-2">Roles</label>
        <div class="space-y-1">
            @foreach ($roles as $role)
                <label class="inline-flex items-center">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                    <span class="ml-2 text-sm text-[#555555]">{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Crear</button>
        <a href="{{ route('users.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-[#555555] hover:bg-gray-50">Cancelar</a>
    </div>
</form>
@endsection
