<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Services\Export\PrestashopProductCsvService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(Request $request, PrestashopProductCsvService $prestashopCsvService)
    {
        if (! auth()->user()->can('export.view')) {
            abort(403);
        }

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

        return view('export.index', [
            'exportPreviewRows' => $previewRows,
            'exportHeaders' => $prestashopCsvService->headers(),
            'exportStats' => [
                'approved_total' => (clone $query)->count(),
                'preview_count' => $previewRows->count(),
                'preview_ready' => $previewRows->where('warnings', [])->count(),
                'preview_warnings' => $previewRows->filter(fn ($row) => $row['warnings'] !== [])->count(),
                'default_tax_rate' => $prestashopCsvService->defaultTaxRatePercent(),
                'default_tax_rule_id' => $prestashopCsvService->defaultTaxRuleId(),
            ],
        ]);
    }

    public function download(Request $request, PrestashopProductCsvService $prestashopCsvService): StreamedResponse
    {
        if (! auth()->user()->can('export.download')) {
            abort(403);
        }

        $approvedOnly = $request->boolean('approved_only', true);

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
        }, 'prestashop-products-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
