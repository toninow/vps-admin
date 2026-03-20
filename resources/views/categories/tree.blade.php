@extends('layouts.app')

@section('title', 'Categorías · Árbol maestro')
@section('page_title', 'Categorías · Árbol maestro')

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-headline text-xl font-bold text-[#555555]">Árbol maestro</h1>
            <p class="mt-1 text-sm text-gray-500">Categorías y subcategorías. Usa Expandir/Contraer para navegar.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn-secondary" onclick="mpCategoriesExpandAll()">Expandir todo</button>
            <button type="button" class="btn-secondary" onclick="mpCategoriesCollapseAll()">Contraer todo</button>
        </div>
    </div>

    <form method="GET" action="{{ route('categories.tree_es') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[220px]">
            <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Buscar</label>
            <input
                id="search"
                name="search"
                type="text"
                value="{{ request('search') }}"
                placeholder="Nombre o slug"
                class="w-full rounded-xl bg-[#f5f3f3] px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#E6007E]"
            >
        </div>
        <button type="submit" class="btn-primary">Aplicar</button>
        @if (filled(request('search')))
            <a href="{{ route('categories.tree_es') }}" class="btn-secondary">Quitar</a>
        @endif
    </form>

    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div id="category-tree" class="space-y-2">
            @forelse ($roots as $root)
                @include('categories._tree_node', [
                    'category' => $root,
                    'childrenByParent' => $childrenByParent,
                    'depth' => 0,
                    'visibleIdsSet' => $visibleIdsSet ?? [],
                ])
            @empty
                <div class="rounded-xl bg-[#f5f3f3] p-6 text-center text-sm text-gray-500">
                    No hay categorías para mostrar.
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    function mpCategoriesExpandAll() {
        document.querySelectorAll('#category-tree details').forEach(d => { d.open = true; });
    }

    function mpCategoriesCollapseAll() {
        document.querySelectorAll('#category-tree details').forEach(d => { d.open = false; });
    }
</script>
@endsection

