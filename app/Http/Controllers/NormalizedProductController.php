<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use App\Models\NormalizedProduct;
use App\Models\Supplier;
use App\Services\Normalization\ImageUrlCleanerService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class NormalizedProductController extends Controller
{
    protected const PAGE_SIZE_OPTIONS = [50, 100, 200, 500, 1000, 5000, 20000, 'all'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', NormalizedProduct::class);

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        $query = NormalizedProduct::with(['supplier', 'supplierImport', 'masterProduct'])
            ->withCount('productEanIssues')
            ->when($request->filled('supplier_import_id'), fn ($q) => $q->where('supplier_import_id', $request->integer('supplier_import_id')))
            ->when($request->filled('catalog_year'), fn ($q) => $q->whereHas('supplierImport', fn ($importQuery) => $importQuery->where('catalog_year', $request->integer('catalog_year'))))
            ->when($request->filled('imported_from'), fn ($q) => $q->whereHas('supplierImport', fn ($importQuery) => $importQuery->whereDate('created_at', '>=', $request->input('imported_from'))))
            ->when($request->filled('imported_to'), fn ($q) => $q->whereHas('supplierImport', fn ($importQuery) => $importQuery->whereDate('created_at', '<=', $request->input('imported_to'))))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('validation_status'), fn ($q) => $q->where('validation_status', $request->validation_status))
            ->when($request->filled('barcode_type'), fn ($q) => $q->where('barcode_type', $request->barcode_type))
            ->when($request->filled('barcode_status'), fn ($q) => $q->where('barcode_status', $request->barcode_status))
            ->when($request->input('price_issue') === 'missing_cost', fn ($q) => $q->whereNull('cost_price'))
            ->when($request->input('price_issue') === 'missing_sale', fn ($q) => $q->whereNull('price_tax_incl'))
            ->when($request->input('price_issue') === 'missing_any', fn ($q) => $q->where(fn ($inner) => $inner->whereNull('cost_price')->orWhereNull('price_tax_incl')))
            ->when($request->input('price_issue') === 'sale_below_cost', fn ($q) => $q->whereNotNull('cost_price')->whereNotNull('price_tax_incl')->whereColumn('price_tax_incl', '<', 'cost_price'))
            ->when($request->input('master_link') === 'with_master', fn ($q) => $q->whereNotNull('master_product_id'))
            ->when($request->input('master_link') === 'without_master', fn ($q) => $q->whereNull('master_product_id'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('ean13', 'like', '%' . $request->search . '%')
                ->orWhere('supplier_reference', 'like', '%' . $request->search . '%')))
            ->latest();

        $perPage = $this->resolvePerPage($request, $query, 50);
        $products = $query->paginate($perPage)->withQueryString();

        return view('products.normalized.index', [
            'products' => $products,
            'suppliers' => $suppliers,
            'pageSizeOptions' => self::PAGE_SIZE_OPTIONS,
        ]);
    }

    public function show(NormalizedProduct $normalizedProduct, Request $request, ImageUrlCleanerService $imageUrlCleaner)
    {
        $this->authorize('view', $normalizedProduct);
        $normalizedProduct->load(['supplier', 'supplierImport', 'supplierImportRow', 'masterProduct', 'productEanIssues', 'productCategorySuggestions.category']);

        $viewData = [
            'normalizedProduct' => $normalizedProduct,
            'imageCandidates' => $imageUrlCleaner->imageCandidatesForProduct($normalizedProduct),
            'storedImageUrls' => $imageUrlCleaner->cleanUrlList(is_array($normalizedProduct->image_urls) ? $normalizedProduct->image_urls : []),
            'rawImageUrls' => $imageUrlCleaner->rawImageCandidatesForProduct($normalizedProduct),
        ];

        if ($request->boolean('modal')) {
            return view('products.normalized._detail_panel', $viewData);
        }

        return view('products.normalized.show', $viewData);
    }

    public function destroy(NormalizedProduct $normalizedProduct): RedirectResponse
    {
        $this->authorize('delete', $normalizedProduct);

        $name = $normalizedProduct->name ?: ('#' . $normalizedProduct->id);
        $normalizedProduct->delete();

        return redirect()->route('products.normalized.index')->with('status', "Producto normalizado eliminado: {$name}.");
    }

    protected function resolvePerPage(Request $request, Builder $query, int $default = 50): int
    {
        $requested = (string) $request->input('per_page', (string) $default);

        if ($requested === 'all') {
            return max(1, (clone $query)->count());
        }

        $allowedNumericOptions = array_values(array_filter(
            self::PAGE_SIZE_OPTIONS,
            static fn (int|string $value): bool => is_int($value)
        ));
        $perPage = (int) $requested;

        return in_array($perPage, $allowedNumericOptions, true)
            ? $perPage
            : $default;
    }
}
