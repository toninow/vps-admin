<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::with('parent')
            ->when($request->filled('parent_id'), fn ($q) => $q->where('parent_id', $request->parent_id))
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(50);

        return view('categories.index', compact('categories'));
    }

    public function treeIndex(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $search = trim((string) $request->input('search', ''));

        $allCategories = Category::query()
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $childrenByParent = $allCategories->groupBy('parent_id');
        $roots = $childrenByParent->get(null, collect());

        $visibleIdsSet = [];
        if ($search !== '') {
            $term = mb_strtolower($search, 'UTF-8');

            $byId = $allCategories->keyBy('id');

            $matching = $allCategories->filter(function (Category $category) use ($term) {
                return Str::contains(mb_strtolower((string) $category->name, 'UTF-8'), $term)
                    || Str::contains(mb_strtolower((string) $category->slug, 'UTF-8'), $term);
            })->pluck('id')->values()->all();

            $queue = collect($matching);
            $visibleIds = [];
            foreach ($matching as $id) {
                $visibleIds[] = $id;
            }

            $visibleIdsSet = array_fill_keys($visibleIds, true);

            // Para que se vea la "ruta", incluimos ancestros de los nodos que coinciden.
            while ($queue->isNotEmpty()) {
                $currentId = $queue->shift();
                $current = $byId->get($currentId);
                if (! $current) {
                    continue;
                }

                if ($current->parent_id && ! isset($visibleIdsSet[$current->parent_id])) {
                    $visibleIdsSet[$current->parent_id] = true;
                    $queue->push($current->parent_id);
                }
            }
        }

        return view('categories.tree', [
            'roots' => $roots,
            'childrenByParent' => $childrenByParent,
            'visibleIdsSet' => $visibleIdsSet,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Category::class);
        $parents = Category::orderBy('name')->get(['id', 'name', 'parent_id']);
        return view('categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['position'] = $data['position'] ?? 0;
        $data['parent_id'] = $request->filled('parent_id') ? $data['parent_id'] : null;
        Category::create($data);

        return redirect()->route('categories.index')->with('status', 'Categoría creada correctamente.');
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);
        $category->load(['parent', 'children', 'masterProducts']);
        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);
        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get(['id', 'name', 'parent_id']);
        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'position' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['parent_id'] = $request->filled('parent_id') ? $data['parent_id'] : null;
        $category->update($data);

        return redirect()->route('categories.show', $category)->with('status', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        $category->delete();
        return redirect()->route('categories.index')->with('status', 'Categoría eliminada.');
    }
}
