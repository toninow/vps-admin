<?php

namespace App\Services\Import;

use App\Models\NormalizedProduct;
use App\Models\SupplierImport;
use Illuminate\Support\Collection;

/**
 * Genera métricas de calidad del mapeo/transformación sobre normalized_products de una importación.
 */
class ImportMappingValidationService
{
    /** @var array<int, string> */
    protected array $targetFields;

    public function __construct(
        protected ImportFieldSemanticsService $fieldSemantics,
    ) {
        // ImportTransformerService::TARGET_FIELDS incluye solo los campos "clásicos".
        // Para QA/validación también necesitamos ver la clasificación centralizada de códigos:
        // ean13 (validación) + barcode_* + ean_status.
        $this->targetFields = array_values(array_unique(array_merge(
            ImportTransformerService::TARGET_FIELDS,
            ['barcode_raw', 'barcode_type', 'barcode_status', 'ean_status']
        )));
    }

    /**
     * Reporte de validación para una importación ya procesada.
     *
     * @return array{total: int, supplier_name: string, filename: string, profile_logical_code: string|null, fields: array<string, array{filled: int, rate: float, samples: array<int, string>, issues: array<int, string>}>}
     */
    public function reportForImport(SupplierImport $import): array
    {
        $import->load('supplier');
        $products = $import->normalizedProducts()->get();
        $profileCode = null;
        try {
            $resolver = app(\App\Services\Suppliers\SupplierProfileResolver::class);
            $profile = $resolver->resolve($import->supplier, $import);
            $profileCode = $profile->getLogicalCode();
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            'total' => $products->count(),
            'supplier_name' => $import->supplier->name ?? '—',
            'filename' => $import->filename_original ?? '—',
            'profile_logical_code' => $profileCode,
            'columns_map' => $import->mapping_snapshot['columns_map'] ?? [],
            'field_semantics' => $this->fieldSemantics->describeImport($import),
            'issue_summary' => $this->buildIssueSummary($products),
            'fields' => $this->buildFieldMetrics($products),
        ];
    }

    /**
     * @param  Collection<int, NormalizedProduct>  $products
     * @return array<string, array{filled: int, rate: float, samples: array<int, string>, issues: array<int, string>}>
     */
    public function buildFieldMetrics(Collection $products): array
    {
        $total = $products->count();
        $result = [];

        foreach ($this->targetFields as $field) {
            $filled = 0;
            $samples = [];
            $issues = [];

            foreach ($products as $p) {
                $value = $this->getFieldValue($p, $field);
                $isEmpty = $this->isEmpty($value, $field);
                if (! $isEmpty) {
                    $filled++;
                    if (count($samples) < 3) {
                        $samples[] = $this->sampleDisplay($value, $field);
                    }
                }
                $this->collectIssues($p, $field, $value, $isEmpty, $issues);
            }

            $rate = $total > 0 ? round($filled / $total * 100, 1) : 0.0;
            $result[$field] = [
                'filled' => $filled,
                'rate' => $rate,
                'samples' => $samples,
                'issues' => array_values(array_unique($issues)),
            ];
        }

        return $result;
    }

    protected function getFieldValue(NormalizedProduct $p, string $field): mixed
    {
        return $p->getAttribute($field);
    }

    protected function isEmpty(mixed $value, string $field): bool
    {
        if ($field === 'image_urls') {
            return ! is_array($value) || count($value) === 0;
        }
        if ($field === 'quantity') {
            return $value === null || $value === '';
        }
        return $value === null || trim((string) $value) === '';
    }

    protected function sampleDisplay(mixed $value, string $field): string
    {
        if ($field === 'image_urls' && is_array($value)) {
            $first = $value[0] ?? '';
            return strlen($first) > 80 ? substr($first, 0, 77) . '...' : $first;
        }
        if ($field === 'description' || $field === 'summary') {
            $s = trim((string) $value);
            return mb_strlen($s) > 80 ? mb_substr($s, 0, 77) . '...' : $s;
        }
        $s = trim((string) $value);
        return mb_strlen($s) > 60 ? mb_substr($s, 0, 57) . '...' : $s;
    }

    protected function collectIssues(NormalizedProduct $p, string $field, mixed $value, bool $isEmpty, array &$issues): void
    {
        if ($field === 'ean13' && ! $isEmpty) {
            $digits = preg_replace('/\D/', '', (string) $value);
            $len = strlen($digits);
            if ($len > 0 && ! in_array($len, [8, 12, 13], true)) {
                $issues[] = "EAN con longitud {$len} (esperado 8/12/13)";
            }
        }

        if ($field === 'barcode_status' && ! $isEmpty) {
            if ((string) $value === 'invalid_ean') {
                $issues[] = 'Código EAN inválido (según clasificación)';
            }
        }

        if ($field === 'ean_status' && ! $isEmpty) {
            if (is_string($value) && str_contains($value, 'invalid_length_')) {
                $issues[] = 'Longitud EAN inválida';
            }
        }

        if ($field === 'quantity' && ! $isEmpty) {
            $q = is_numeric($value) ? (int) $value : 0;
            if ($q < 0) {
                $issues[] = 'Cantidad negativa';
            }
        }
        if (in_array($field, ['price_tax_incl', 'cost_price'], true) && ! $isEmpty) {
            $v = is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null;
            if ($v !== null && $v < 0) {
                $issues[] = 'Precio negativo';
            }
        }
    }

    /**
     * @param  Collection<int, NormalizedProduct>  $products
     * @return array<string, int>
     */
    protected function buildIssueSummary(Collection $products): array
    {
        return [
            'barcode_ok' => $products->where('barcode_status', 'ok')->count(),
            'barcode_non_ean' => $products->where('barcode_status', 'non_ean')->count(),
            'barcode_invalid' => $products->where('barcode_status', 'invalid_ean')->count(),
            'barcode_missing' => $products->where('barcode_status', 'missing')->count(),
            'missing_cost_price' => $products->filter(fn (NormalizedProduct $product) => $product->cost_price === null)->count(),
            'missing_sale_price' => $products->filter(fn (NormalizedProduct $product) => $product->price_tax_incl === null)->count(),
            'sale_below_cost_price' => $products->filter(fn (NormalizedProduct $product) => $product->cost_price !== null && $product->price_tax_incl !== null && (float) $product->price_tax_incl < (float) $product->cost_price)->count(),
            'with_ean_issues' => $products->filter(fn (NormalizedProduct $product) => in_array($product->ean_status, ['invalid_length', 'invalid_checksum', 'invalid_chars', 'empty'], true))->count(),
        ];
    }
}
