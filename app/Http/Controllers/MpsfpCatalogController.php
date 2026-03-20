<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Models\ProductCategorySuggestion;
use App\Models\Project;
use App\Models\Supplier;
use App\Services\Categories\CategoryPathBuilderService;
use App\Services\Export\MasterApprovalService;
use App\Services\Export\PrestashopProductCsvService;
use App\Services\Normalization\ImageUrlCleanerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class MpsfpCatalogController extends Controller
{
    protected const PAGE_SIZE_OPTIONS = [50, 100, 200, 500, 1000, 5000, 20000, 'all'];

    public function normalizedIndex(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'normalizados');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        $query = NormalizedProduct::with(['supplier', 'supplierImport', 'masterProduct'])
            ->withCount('productEanIssues')
            ->addSelect([
                'same_ean_suppliers_count' => NormalizedProduct::query()
                    ->from('normalized_products as np_same_suppliers')
                    ->selectRaw('COUNT(DISTINCT np_same_suppliers.supplier_id)')
                    ->whereColumn('np_same_suppliers.ean13', 'normalized_products.ean13')
                    ->whereNotNull('np_same_suppliers.ean13')
                    ->where('np_same_suppliers.ean13', '<>', ''),
                'same_ean_rows_count' => NormalizedProduct::query()
                    ->from('normalized_products as np_same_rows')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('np_same_rows.ean13', 'normalized_products.ean13')
                    ->whereNotNull('np_same_rows.ean13')
                    ->where('np_same_rows.ean13', '<>', ''),
            ])
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

        $statsBaseQuery = clone $query;
        $normalizedStats = [
            'total' => (clone $statsBaseQuery)->count(),
            'pending_review' => (clone $statsBaseQuery)->whereIn('validation_status', ['warning', 'error'])->count(),
            'errors' => (clone $statsBaseQuery)->where('validation_status', 'error')->count(),
            'suggested_categories' => (clone $statsBaseQuery)->where('category_status', 'suggested')->count(),
        ];

        $perPage = $this->resolvePerPage($request, $query, 50);
        $products = $query->paginate($perPage)->withQueryString();

        return view('products.normalized.index', [
            'products' => $products,
            'suppliers' => $suppliers,
            'normalizedStats' => $normalizedStats,
            'pageSizeOptions' => self::PAGE_SIZE_OPTIONS,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
        ]);
    }

    public function crossSupplierIndex(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'cruce_proveedores');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        $groupsQuery = DB::table('normalized_products as np')
            ->join('suppliers as s', 's.id', '=', 'np.supplier_id')
            ->selectRaw('np.ean13, COUNT(*) as rows_count, COUNT(DISTINCT np.supplier_id) as suppliers_count, GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR " | ") as supplier_names, MAX(np.updated_at) as last_seen_at')
            ->whereNotNull('np.ean13')
            ->where('np.ean13', '<>', '')
            ->groupBy('np.ean13')
            ->havingRaw('COUNT(DISTINCT np.supplier_id) > 1')
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $supplierId = $request->integer('supplier_id');

                $query->whereExists(function ($sub) use ($supplierId) {
                    $sub->selectRaw('1')
                        ->from('normalized_products as np_filter')
                        ->whereColumn('np_filter.ean13', 'np.ean13')
                        ->where('np_filter.supplier_id', $supplierId);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->input('search') . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('np.ean13', 'like', $term)
                        ->orWhereExists(function ($sub) use ($term) {
                            $sub->selectRaw('1')
                                ->from('normalized_products as np_search')
                                ->whereColumn('np_search.ean13', 'np.ean13')
                                ->where(function ($q) use ($term) {
                                    $q->where('np_search.name', 'like', $term)
                                        ->orWhere('np_search.summary', 'like', $term)
                                        ->orWhere('np_search.supplier_reference', 'like', $term)
                                        ->orWhere('np_search.brand', 'like', $term);
                                });
                        });
                });
            })
            ->orderByDesc('suppliers_count')
            ->orderByDesc('rows_count')
            ->orderBy('np.ean13');

        $groups = $groupsQuery->paginate(25)->withQueryString();
        $eans = collect($groups->items())->pluck('ean13')->filter()->values();

        $productsByEan = $eans->isEmpty()
            ? collect()
            : NormalizedProduct::with(['supplier', 'masterProduct'])
                ->whereIn('ean13', $eans)
                ->orderBy('ean13')
                ->orderBy('supplier_id')
                ->orderBy('name')
                ->get()
                ->groupBy('ean13');

        return view('projects.mpsfp.cross-suppliers.index', [
            'groups' => $groups,
            'productsByEan' => $productsByEan,
            'suppliers' => $suppliers,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
        ]);
    }

    public function normalizedShow(Project $project, NormalizedProduct $normalizedProduct, Request $request, ImageUrlCleanerService $imageUrlCleaner): View
    {
        $this->ensureMpsfpAbility($project, 'normalizados');

        $normalizedProduct->load(['supplier', 'supplierImport', 'supplierImportRow', 'masterProduct', 'category.parent', 'productEanIssues', 'productCategorySuggestions.category.parent']);
        $storedImageUrls = $imageUrlCleaner->cleanUrlList(is_array($normalizedProduct->image_urls) ? $normalizedProduct->image_urls : []);
        $rawImageUrls = $imageUrlCleaner->rawImageCandidatesForProduct($normalizedProduct);
        $imageCandidates = $imageUrlCleaner->imageCandidatesForProduct($normalizedProduct);

        $viewData = [
            'normalizedProduct' => $normalizedProduct,
            'imageCandidates' => $imageCandidates,
            'storedImageUrls' => $storedImageUrls,
            'rawImageUrls' => $rawImageUrls,
            'mpsfpProject' => $project,
        ];

        if ($request->boolean('modal')) {
            return view('products.normalized._detail_panel', $viewData);
        }

        return view('products.normalized.show', $viewData + [
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function categoryReviewIndex(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'categorias');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $status = (string) $request->input('status', 'pending');
        $minScore = (float) $request->input('min_score', 0);

        $query = NormalizedProduct::query()
            ->with([
                'supplier',
                'masterProduct',
                'productCategorySuggestions' => fn ($q) => $q->with('category.parent')->orderByDesc('score')->orderBy('id'),
            ])
            ->withCount('productCategorySuggestions')
            ->withMax('productCategorySuggestions as top_category_score', 'score')
            ->whereHas('productCategorySuggestions', function ($q) use ($minScore) {
                if ($minScore > 0) {
                    $q->where('score', '>=', $minScore);
                }
            })
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->input('search') . '%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('ean13', 'like', $term)
                        ->orWhere('supplier_reference', 'like', $term);
                });
            })
            ->when($status === 'pending', fn ($q) => $q->whereNull('category_id'))
            ->when($status === 'assigned', fn ($q) => $q->whereNotNull('category_id'))
            ->orderByDesc('top_category_score')
            ->orderByDesc('id');

        $products = $query->paginate(30)->withQueryString();

        // Datos para renderizar el "Árbol maestro" embebido en esta pantalla.
        $allCategories = Category::query()
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name')
            ->get();
        $childrenByParent = $allCategories->groupBy('parent_id');
        $roots = $childrenByParent->get(null, collect());

        return view('projects.mpsfp.categories.review', [
            'products' => $products,
            'suppliers' => $suppliers,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
            'reviewStatus' => $status,
            'minScore' => $minScore,
            'roots' => $roots,
            'childrenByParent' => $childrenByParent,
        ]);
    }

    public function acceptCategorySuggestion(
        Project $project,
        NormalizedProduct $normalizedProduct,
        ProductCategorySuggestion $suggestion,
        Request $request,
        CategoryPathBuilderService $pathBuilder,
    ): RedirectResponse {
        $this->ensureMpsfpAbility($project, 'categorias', 'edit');

        if ($suggestion->normalized_product_id !== $normalizedProduct->id) {
            abort(404);
        }

        $suggestion->loadMissing('category');
        if (! $suggestion->category) {
            return redirect()->back()->withErrors(['category' => 'La sugerencia seleccionada no tiene categoría asociada.']);
        }

        DB::transaction(function () use ($normalizedProduct, $suggestion, $pathBuilder) {
            $category = $suggestion->category;
            $path = $pathBuilder->buildExportPath($category);

            $normalizedProduct->update([
                'category_id' => $category->id,
                'category_status' => 'suggested',
                'category_path_export' => $path,
            ]);

            if ($normalizedProduct->masterProduct) {
                $normalizedProduct->masterProduct->update([
                    'category_id' => $category->id,
                    'category_status' => 'suggested',
                    'category_path_export' => $path,
                ]);
            }

            ProductCategorySuggestion::query()
                ->where('normalized_product_id', $normalizedProduct->id)
                ->update(['accepted_at' => null]);

            $suggestion->update(['accepted_at' => now()]);
        });

        return redirect()->back()->with('status', 'Sugerencia de categoría seleccionada. La confirmación final queda pendiente para la app.');
    }

    public function dismissCategorySuggestion(
        Project $project,
        NormalizedProduct $normalizedProduct,
        ProductCategorySuggestion $suggestion,
    ): RedirectResponse {
        $this->ensureMpsfpAbility($project, 'categorias', 'edit');

        if ($suggestion->normalized_product_id !== $normalizedProduct->id) {
            abort(404);
        }

        $suggestion->delete();

        return redirect()->back()->with('status', 'Sugerencia descartada.');
    }

    public function bulkApplyCategorySuggestions(
        Project $project,
        Request $request,
        CategoryPathBuilderService $pathBuilder,
    ): RedirectResponse {
        $this->ensureMpsfpAbility($project, 'categorias', 'edit');

        $products = $this->selectedNormalizedProducts($request);
        $applied = 0;
        $skippedAmbiguous = 0;

        foreach ($products as $product) {
            $topScore = $product->productCategorySuggestions()->max('score');

            if ($topScore === null) {
                continue;
            }

            $topSuggestions = $product->productCategorySuggestions()
                ->with('category')
                ->where('score', $topScore)
                ->orderBy('id')
                ->get();

            if ($topSuggestions->count() !== 1) {
                $skippedAmbiguous++;
                continue;
            }

            $suggestion = $topSuggestions->first();

            if (! $suggestion || ! $suggestion->category) {
                continue;
            }

            DB::transaction(function () use ($product, $suggestion, $pathBuilder) {
                $path = $pathBuilder->buildExportPath($suggestion->category);

                $product->update([
                    'category_id' => $suggestion->category_id,
                    'category_status' => 'suggested',
                    'category_path_export' => $path,
                ]);

                if ($product->masterProduct) {
                    $product->masterProduct->update([
                        'category_id' => $suggestion->category_id,
                        'category_status' => 'suggested',
                        'category_path_export' => $path,
                    ]);
                }

                ProductCategorySuggestion::query()
                    ->where('normalized_product_id', $product->id)
                    ->update(['accepted_at' => null]);

                $suggestion->update(['accepted_at' => now()]);
            });

            $applied++;
        }

        if ($applied === 0) {
            $error = 'No se pudo aplicar ninguna sugerencia de categoría en la selección actual.';
            if ($skippedAmbiguous > 0) {
                $error .= " Hay {$skippedAmbiguous} productos con empate en la mejor sugerencia.";
            }

            return redirect()->back()->withErrors(['bulk' => $error]);
        }

        $status = "Sugerencias de categoría aplicadas: {$applied}.";
        if ($skippedAmbiguous > 0) {
            $status .= " Ambiguas sin aplicar: {$skippedAmbiguous}.";
        }

        return redirect()->back()->with('status', $status);
    }

    public function normalizedDestroy(Project $project, NormalizedProduct $normalizedProduct): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'normalizados', 'edit');

        $name = $normalizedProduct->name ?: ('#' . $normalizedProduct->id);
        $normalizedProduct->delete();

        return redirect()->route('projects.mpsfp.normalized.index', $project)->with('status', "Producto normalizado eliminado: {$name}.");
    }

    public function masterIndex(Project $project, Request $request): View
    {
        $this->ensureMpsfpAbility($project, 'maestros');

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

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

        return view('products.master.index', [
            'products' => $products,
            'suppliers' => $suppliers,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => $request->user()->mpsfpCapabilities(),
        ]);
    }

    public function masterShow(Project $project, MasterProduct $masterProduct): View
    {
        $this->ensureMpsfpAbility($project, 'maestros');

        $masterProduct->load(['category', 'masterProductSuppliers', 'normalizedProducts.supplier', 'productImages', 'stockChanges', 'approvedBy']);

        return view('products.master.show', [
            'masterProduct' => $masterProduct,
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function masterApprove(
        Project $project,
        Request $request,
        MasterProduct $masterProduct,
        MasterApprovalService $masterApprovalService
    ): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'maestros', 'approve');

        $approvable = $masterApprovalService->collectApprovableIds([$masterProduct->id], true, null, 1);

        if ($approvable->isEmpty()) {
            return redirect()->back()->withErrors(['approve' => 'Este maestro no cumple todavía la exportación real y no puede aprobarse.']);
        }

        if (! $masterProduct->is_approved) {
            $masterApprovalService->approve([$masterProduct->id], $request->user()->id);
        }

        return redirect()->back()->with('status', 'Producto maestro aprobado para exportación.');
    }

    public function masterBulkApprove(
        Project $project,
        Request $request,
        MasterApprovalService $masterApprovalService
    ): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'maestros', 'approve');

        $masters = $this->selectedMasterProducts($request);
        $count = $masters->count();

        if ($count === 0) {
            return redirect()->back()->withErrors(['bulk' => 'No se seleccionó ningún maestro para aprobar.']);
        }

        $approvedIds = $masterApprovalService->collectApprovableIds($masters->pluck('id')->all());
        $approvedCount = $masterApprovalService->approve($approvedIds, $request->user()->id);
        $skippedCount = $count - $approvedCount;

        if ($approvedCount === 0) {
            return redirect()->back()->withErrors(['bulk' => 'Ninguno de los maestros seleccionados cumple la exportación real para ser aprobado.']);
        }

        $message = "Maestros aprobados: {$approvedCount}.";
        if ($skippedCount > 0) {
            $message .= " Omitidos por no ser exportables aún: {$skippedCount}.";
        }

        return redirect()->back()->with('status', $message);
    }

    public function masterUnapprove(Project $project, MasterProduct $masterProduct): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'maestros', 'approve');

        if ($masterProduct->is_approved) {
            $masterProduct->update([
                'is_approved' => false,
                'approved_at' => null,
                'approved_by_id' => null,
            ]);
        }

        return redirect()->back()->with('status', 'La aprobación del producto maestro se ha retirado.');
    }

    public function masterBulkUnapprove(Project $project, Request $request): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'maestros', 'approve');

        $masters = $this->selectedMasterProducts($request);
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

    public function masterDestroy(Project $project, MasterProduct $masterProduct): RedirectResponse
    {
        $this->ensureMpsfpAbility($project, 'maestros', 'delete');

        $name = $masterProduct->name ?: ('#' . $masterProduct->id);
        $masterProduct->delete();

        return redirect()->route('projects.mpsfp.master.index', $project)->with('status', "Producto maestro eliminado: {$name}.");
    }

    public function exportIndex(Project $project, Request $request, PrestashopProductCsvService $prestashopCsvService): View
    {
        $this->ensureMpsfpAbility($project, 'exportacion');

        $query = MasterProduct::query()
            ->with([
                'masterProductSuppliers.normalizedProduct.supplier',
                'masterProductSuppliers.normalizedProduct.supplierImport',
                'normalizedProducts.supplier',
                'normalizedProducts.supplierImport',
            ])
            ->when($request->boolean('approved_only', true), fn ($q) => $q->where('is_approved', true))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('ean13', 'like', '%' . $request->input('search') . '%')
                ->orWhere('reference', 'like', '%' . $request->input('search') . '%')))
            ->latest();

        $previewProducts = (clone $query)->limit(50)->get();
        $previewRows = $previewProducts->map(function (MasterProduct $product) use ($prestashopCsvService) {
            return [
                'masterProduct' => $product,
                ...$prestashopCsvService->buildForMasterProduct($product),
            ];
        });

        $readyCount = $previewRows->where('warnings', [])->count();
        $warningCount = $previewRows->count() - $readyCount;

        return view('export.index', [
            'exportPreviewRows' => $previewRows,
            'exportHeaders' => $prestashopCsvService->headers(),
            'exportStats' => [
                'approved_total' => (clone $query)->count(),
                'preview_count' => $previewRows->count(),
                'preview_ready' => $readyCount,
                'preview_warnings' => $warningCount,
                'default_tax_rate' => $prestashopCsvService->defaultTaxRatePercent(),
                'default_tax_rule_id' => $prestashopCsvService->defaultTaxRuleId(),
            ],
            'mpsfpProject' => $project,
            'mpsfpSections' => $this->mpsfpSections($project),
            'mpsfpAccess' => auth()->user()->mpsfpCapabilities(),
        ]);
    }

    public function exportDownload(Project $project, Request $request, PrestashopProductCsvService $prestashopCsvService): StreamedResponse
    {
        $this->ensureMpsfpAbility($project, 'exportacion', 'download');

        $approvedOnly = $request->boolean('approved_only', true);

        $filename = 'prestashop-products-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($approvedOnly, $prestashopCsvService) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $prestashopCsvService->headers(), ';');

            MasterProduct::query()
                ->with([
                    'masterProductSuppliers.normalizedProduct.supplier',
                    'masterProductSuppliers.normalizedProduct.supplierImport',
                    'normalizedProducts.supplier',
                    'normalizedProducts.supplierImport',
                ])
                ->when($approvedOnly, fn ($q) => $q->where('is_approved', true))
                ->orderBy('id')
                ->chunkById(250, function ($products) use ($handle, $prestashopCsvService) {
                    foreach ($products as $product) {
                        $payload = $prestashopCsvService->buildForMasterProduct($product);
                        if (! data_get($payload, 'metrics.is_exportable', false)) {
                            continue;
                        }
                        fputcsv($handle, array_values($payload['row']), ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function ensureMpsfpAbility(Project $project, string $section, string $ability = 'view'): void
    {
        $this->authorize('view', $project);

        if ($project->slug !== 'mpsfp') {
            abort(404);
        }

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

    protected function selectedMasterProducts(Request $request): Collection
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

    protected function selectedNormalizedProducts(Request $request): Collection
    {
        $ids = collect($request->input('normalized_product_ids', []))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? collect()
            : NormalizedProduct::query()->whereIn('id', $ids)->get(['id']);
    }
}
