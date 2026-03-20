<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class MasterProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MasterProduct::class);

        $products = MasterProduct::with(['category', 'normalizedProducts.supplier', 'normalizedProducts.productEanIssues'])
            ->when($request->filled('is_approved'), fn ($q) => $q->where('is_approved', $request->boolean('is_approved')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->whereHas('normalizedProducts', fn ($inner) => $inner->where('supplier_id', $request->integer('supplier_id'))))
            ->when($request->input('export_ready') === '1', fn ($q) => $q->whereNotNull('price_tax_incl')->whereNotNull('cost_price')->whereColumn('price_tax_incl', '>=', 'cost_price'))
            ->when($request->input('export_ready') === '0', fn ($q) => $q->where(fn ($inner) => $inner
                ->whereNull('price_tax_incl')
                ->orWhereNull('cost_price')
                ->orWhereColumn('price_tax_incl', '<', 'cost_price')))
            ->when($request->input('with_category') === '1', fn ($q) => $q->whereNotNull('category_id'))
            ->when($request->input('with_category') === '0', fn ($q) => $q->whereNull('category_id'))
            ->when($request->input('ean_review') === 'clean', fn ($q) => $q->whereDoesntHave('normalizedProducts.productEanIssues', fn ($inner) => $inner->whereNull('resolved_at')))
            ->when($request->input('ean_review') === 'issues', fn ($q) => $q->whereHas('normalizedProducts.productEanIssues', fn ($inner) => $inner->whereNull('resolved_at')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('ean13', 'like', '%' . $request->search . '%')
                ->orWhere('reference', 'like', '%' . $request->search . '%')))
            ->latest()
            ->paginate(20);

        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);

        return view('products.master.index', compact('products', 'suppliers'));
    }

    public function show(MasterProduct $masterProduct)
    {
        $this->authorize('view', $masterProduct);
        $masterProduct->load(['category', 'masterProductSuppliers', 'normalizedProducts', 'productImages', 'stockChanges']);
        return view('products.master.show', compact('masterProduct'));
    }

    public function approve(Request $request, MasterProduct $masterProduct): RedirectResponse
    {
        $this->authorize('approve', $masterProduct);

        if (! $masterProduct->is_approved) {
            $masterProduct->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by_id' => $request->user()->id,
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Producto maestro aprobado para exportación.');
    }

    public function unapprove(MasterProduct $masterProduct): RedirectResponse
    {
        $this->authorize('approve', $masterProduct);

        if ($masterProduct->is_approved) {
            $masterProduct->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by_id' => null,
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'La aprobación del producto maestro se ha retirado.');
    }

    public function destroy(MasterProduct $masterProduct): RedirectResponse
    {
        $this->authorize('delete', $masterProduct);

        $name = $masterProduct->name ?: ('#' . $masterProduct->id);
        $masterProduct->delete();

        return redirect()->route('products.master.index')->with('status', "Producto maestro eliminado: {$name}.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('master_products.approve'), 403);

        $masters = $this->selectedMasters($request);
        $count = $masters->count();

        if ($count === 0) {
            return redirect()->back()->withErrors(['bulk' => 'No se seleccionó ningún maestro para aprobar.']);
        }

        MasterProduct::query()
            ->whereIn('id', $masters->pluck('id'))
            ->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by_id' => $request->user()->id,
            ]);

        return redirect()->back()->with('status', "Maestros aprobados: {$count}.");
    }

    public function bulkUnapprove(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('master_products.approve'), 403);

        $masters = $this->selectedMasters($request);
        $count = $masters->count();

        if ($count === 0) {
            return redirect()->back()->withErrors(['bulk' => 'No se seleccionó ningún maestro para retirar.']);
        }

        MasterProduct::query()
            ->whereIn('id', $masters->pluck('id'))
            ->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by_id' => null,
            ]);

        return redirect()->back()->with('status', "Aprobación retirada en {$count} maestros.");
    }

    protected function selectedMasters(Request $request): Collection
    {
        $ids = collect($request->input('master_product_ids', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : MasterProduct::query()->whereIn('id', $ids)->get(['id']);
    }
}
