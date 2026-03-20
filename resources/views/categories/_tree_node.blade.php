@php
    /** @var \App\Models\Category $category */
    $children = $childrenByParent->get($category->id, collect());
    $visibleIdsSet = $visibleIdsSet ?? [];
    $isVisible = empty($visibleIdsSet) || isset($visibleIdsSet[$category->id]);

    if (! empty($visibleIdsSet)) {
        $children = $children instanceof \Illuminate\Support\Collection
            ? $children->filter(fn ($c) => isset($visibleIdsSet[$c->id]))
            : collect($children)->filter(fn ($c) => isset($visibleIdsSet[$c->id]));
    }

    $hasChildren = $children instanceof \Illuminate\Support\Collection ? $children->isNotEmpty() : !empty($children);
    $statusClass = $category->is_active ? 'status-green' : 'status-gray';
    $indentPx = (int) $depth * 12;
@endphp

@if (! $isVisible)
    {{-- Nodo no visible en modo búsqueda. --}}
@else
@if ($hasChildren)
    <details class="rounded-xl bg-[#f5f3f3] p-3" @if($depth === 0) open @endif>
        <summary class="cursor-pointer list-none">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="material-symbols-outlined text-[#E6007E]">account_tree</span>
                    <span class="truncate font-semibold text-[#555555]">{{ $category->name }}</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="status-badge {{ $statusClass }}">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</span>
                    <span class="text-xs font-bold text-[#646464]">{{ $children->count() }}</span>
                </div>
            </div>
        </summary>

        <div class="mt-3 space-y-2" style="margin-left: {{ $indentPx }}px">
            @foreach ($children as $child)
                @include('categories._tree_node', [
                    'category' => $child,
                    'childrenByParent' => $childrenByParent,
                    'depth' => $depth + 1,
                    'visibleIdsSet' => $visibleIdsSet,
                ])
            @endforeach
        </div>
    </details>
@else
    <div class="rounded-xl bg-white p-3" style="margin-left: {{ $indentPx }}px">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="material-symbols-outlined text-[#646464]">label</span>
                <span class="truncate font-semibold text-[#555555]">{{ $category->name }}</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="status-badge {{ $statusClass }}">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</span>
            </div>
        </div>
    </div>
@endif
@endif

