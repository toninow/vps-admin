<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\Suppliers\SupplierCatalogHistoryService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorize('create', Supplier::class);
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Supplier::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:suppliers,slug'],
            'code' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('status', 'Proveedor creado correctamente.');
    }

    public function show(Supplier $supplier, SupplierCatalogHistoryService $catalogHistoryService)
    {
        $this->authorize('view', $supplier);
        $supplier->load(['supplierFieldMappings', 'supplierImports' => fn ($q) => $q->latest()->take(5)]);
        $catalogHistory = $catalogHistoryService->buildForSupplier($supplier);

        return view('suppliers.show', compact('supplier', 'catalogHistory'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('update', $supplier);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:suppliers,slug,' . $supplier->id],
            'code' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $supplier->update($data);

        return redirect()->route('suppliers.show', $supplier)->with('status', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('status', 'Proveedor eliminado.');
    }
}
