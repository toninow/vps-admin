<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Supplier;
use App\Services\Suppliers\SupplierCatalogHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MpsfpSupplierController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'proveedores');

        $suppliers = Supplier::query()
            ->withCount(['supplierImports', 'normalizedProducts'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15);

        $supplierStats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', true)->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
            'recent' => Supplier::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'supplierStats' => $supplierStats,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
        ]);
    }

    public function create(Project $project): View
    {
        $this->ensureMpsfpAbility($project, 'proveedores', 'create');

        return view('suppliers.create', [
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function store(Project $project, Request $request): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'proveedores', 'create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:suppliers,slug'],
            'code' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $supplier = Supplier::create($data);

        return redirect()->route('projects.mpsfp.suppliers.show', [$project, $supplier])->with('status', 'Proveedor creado correctamente.');
    }

    public function show(Project $project, Supplier $supplier, SupplierCatalogHistoryService $catalogHistoryService): View
    {
        $this->ensureMpsfpProject($project);

        $supplier->load(['supplierFieldMappings', 'supplierImports' => fn ($q) => $q->latest()->take(5)]);
        $catalogHistory = $catalogHistoryService->buildForSupplier($supplier);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'catalogHistory' => $catalogHistory,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function edit(Project $project, Supplier $supplier): View
    {
        $this->ensureMpsfpAbility($project, 'proveedores', 'edit');

        $supplier->loadCount(['supplierImports', 'normalizedProducts']);
        $supplier->load([
            'supplierImports' => fn ($query) => $query->latest()->take(3),
        ]);

        return view('suppliers.edit', [
            'supplier' => $supplier,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function update(Project $project, Request $request, Supplier $supplier): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'proveedores', 'edit');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:suppliers,slug,' . $supplier->id],
            'code' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $supplier->update($data);

        return redirect()->route('projects.mpsfp.suppliers.show', [$project, $supplier])->with('status', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Project $project, Supplier $supplier): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'proveedores', 'edit');

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('projects.mpsfp.suppliers.index', $project)->with('status', "Proveedor eliminado: {$name}.");
    }

    protected function ensureMpsfpProject(Project $project): void
    {
        $this->authorize('view', $project);

        if ($project->slug !== 'mpsfp') {
            abort(404);
        }
    }

    protected function ensureMpsfpAbility(Project $project, string $section, string $ability = 'view'): void
    {
        $this->ensureMpsfpProject($project);

        if (! auth()->user()->canAccessMpsfpSection($section, $ability)) {
            abort(403);
        }
    }

    protected function mpsfpSections(Project $project): array
    {
        $capabilities = auth()->user()->mpsfpCapabilities()['sections'];

        return [
            'proveedores' => array_merge($capabilities['proveedores'], ['url' => route('projects.mpsfp.suppliers.index', $project)]),
            'importaciones' => array_merge($capabilities['importaciones'], ['url' => route('projects.mpsfp.imports.index', $project)]),
            'normalizados' => array_merge($capabilities['normalizados'], ['url' => route('projects.mpsfp.normalized.index', $project)]),
            'maestros' => array_merge($capabilities['maestros'], ['url' => route('projects.mpsfp.master.index', $project)]),
            'ean' => array_merge($capabilities['ean'], ['url' => route('projects.mpsfp.section', ['project' => $project, 'section' => 'ean'])]),
            'duplicados' => array_merge($capabilities['duplicados'], ['url' => route('projects.mpsfp.section', ['project' => $project, 'section' => 'duplicados'])]),
            'cruce_proveedores' => array_merge($capabilities['cruce_proveedores'], ['url' => route('projects.mpsfp.cross-suppliers.index', $project)]),
            'categorias' => array_merge($capabilities['categorias'], ['url' => route('projects.mpsfp.categories.review', $project)]),
            'exportacion' => array_merge($capabilities['exportacion'], ['url' => route('projects.mpsfp.export.index', $project)]),
        ];
    }
}
