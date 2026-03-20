@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Category> $roots */
    /** @var \Illuminate\Support\Collection<int|null, \Illuminate\Support\Collection<int, \App\Models\Category>> $childrenByParent */
    $roots = $roots ?? collect();
@endphp

<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-headline text-lg font-bold text-[#555555]">Árbol maestro</p>
            <p class="mt-1 text-sm text-gray-500">Categorías y subcategorías (expandir/contraer).</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn-secondary" onclick="mpMasterTreeExpandAllEmbed()">Expandir todo</button>
            <button type="button" class="btn-secondary" onclick="mpMasterTreeCollapseAllEmbed()">Contraer todo</button>
        </div>
    </div>

    <div id="master-tree-embed" class="mt-4 space-y-2">
        @forelse ($roots as $root)
            @include('categories._tree_node', ['category' => $root, 'childrenByParent' => $childrenByParent, 'depth' => 0])
        @empty
            <div class="rounded-xl bg-[#f5f3f3] p-6 text-center text-sm text-gray-500">
                No hay categorías para mostrar.
            </div>
        @endforelse
    </div>
</div>

<script>
    function mpMasterTreeExpandAllEmbed() {
        document.querySelectorAll('#master-tree-embed details').forEach(d => { d.open = true; });
    }

    function mpMasterTreeCollapseAllEmbed() {
        document.querySelectorAll('#master-tree-embed details').forEach(d => { d.open = false; });
    }
</script>

