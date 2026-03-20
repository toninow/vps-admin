<?php

namespace App\Services\Export;

use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Models\Setting;
use App\Models\SupplierImport;
use App\Services\Import\ImportFieldSemanticsService;
use App\Services\Normalization\ProductTextFormatterService;
use App\Support\CategoryPathFormatter;
use Throwable;

class PrestashopProductCsvService
{
    protected array $importSemanticsCache = [];

    public function __construct(
        protected ImportFieldSemanticsService $fieldSemanticsService,
        protected ProductTextFormatterService $textFormatter,
    ) {
    }

    /**
     * @return string[]
     */
    public function headers(): array
    {
        return [
            'Reference',
            'EAN13',
            'Name',
            'Summary',
            'Description',
            'Categories',
            'Brand',
            'Quantity',
            'Tax rules ID',
            'Wholesale price',
            'Price tax excluded',
            'Price tax included',
            'Active',
        ];
    }

    /**
     * @return array{
     *   row: array<string, string>,
     *   warnings: string[],
     *   source: array<string, string|null>,
     *   metrics: array<string, bool|float|null>
     * }
     */
    public function buildForMasterProduct(MasterProduct $masterProduct, ?NormalizedProduct $sourceProduct = null, ?float $taxRate = null): array
    {
        $sourceProduct ??= $this->resolveSourceProduct($masterProduct);

        $taxRate ??= $this->defaultTaxRatePercent();
        $taxFactor = 1 + ($taxRate / 100);

        $saleTaxInfo = $this->resolveTaxInfo($sourceProduct, 'price_tax_incl', 'including_vat');
        $costTaxInfo = $this->resolveTaxInfo($sourceProduct, 'cost_price', 'excluding_vat');
        $saleTaxMode = $saleTaxInfo['mode'];
        $costTaxMode = $costTaxInfo['mode'];

        $warnings = [];
        $salePriceStored = $this->normalizeNumeric($masterProduct->price_tax_incl);
        $costPriceStored = $this->normalizeNumeric($masterProduct->cost_price);

        $salePriceIncl = null;
        $salePriceExcl = null;
        if ($salePriceStored !== null) {
            if ($saleTaxMode === 'excluding_vat') {
                $salePriceExcl = $salePriceStored;
                $salePriceIncl = $salePriceStored * $taxFactor;
            } else {
                $salePriceIncl = $salePriceStored;
                $salePriceExcl = $taxFactor > 0 ? ($salePriceStored / $taxFactor) : $salePriceStored;
            }

            if ($saleTaxInfo['inferred']) {
                $warnings[] = 'Precio de venta sin evidencia fiscal clara; se exporta asumiendo que ya incluye IVA.';
            }
        } else {
            $warnings[] = 'Falta precio de venta para PrestaShop.';
        }

        $wholesalePrice = null;
        if ($costPriceStored !== null) {
            $wholesalePrice = $costTaxMode === 'including_vat' && $taxFactor > 0
                ? ($costPriceStored / $taxFactor)
                : $costPriceStored;

            if ($costTaxInfo['inferred']) {
                $warnings[] = 'Precio de compra sin semántica fiscal clara; se exporta como coste sin IVA.';
            }
        } else {
            $warnings[] = 'Falta precio de compra para PrestaShop.';
        }

        if ($salePriceExcl !== null && $wholesalePrice !== null && $salePriceExcl < $wholesalePrice) {
            $warnings[] = 'El precio de venta sin IVA queda por debajo del coste.';
            $salePriceExcl = null;
            $salePriceIncl = null;
        }

        $formattedCharacteristics = $this->textFormatter->formatCharacteristics(
            $masterProduct->description,
            $masterProduct->name
        );

        $blockingWarnings = collect($warnings)->contains(fn (string $warning) => in_array($warning, [
            'Falta precio de venta para PrestaShop.',
            'Falta precio de compra para PrestaShop.',
            'El precio de venta sin IVA queda por debajo del coste.',
        ], true));

        $row = [
            'Reference' => (string) ($masterProduct->reference ?? ''),
            'EAN13' => (string) ($masterProduct->ean13 ?? ''),
            'Name' => (string) ($masterProduct->name ?? ''),
            'Summary' => (string) ($masterProduct->summary ?? ''),
            'Description' => $formattedCharacteristics !== '' ? $formattedCharacteristics : (string) ($masterProduct->description ?? ''),
            'Categories' => CategoryPathFormatter::formatForDisplay($masterProduct->category_path_export),
            'Brand' => (string) ($masterProduct->brand ?? ''),
            'Quantity' => (string) (($masterProduct->quantity ?? 0)),
            'Tax rules ID' => (string) ($masterProduct->tax_rule_id ?? $this->defaultTaxRuleId()),
            'Wholesale price' => $this->formatDecimal($wholesalePrice),
            'Price tax excluded' => $this->formatDecimal($salePriceExcl),
            'Price tax included' => $this->formatDecimal($salePriceIncl),
            'Active' => (string) ((int) ($masterProduct->active ?? 0)),
        ];

        return [
            'row' => $row,
            'warnings' => array_values(array_unique($warnings)),
            'source' => [
                'supplier' => $this->loadedSupplierName($sourceProduct),
                'import_filename' => $this->loadedImportFilename($sourceProduct),
                'sale_tax_mode' => $saleTaxMode,
                'cost_tax_mode' => $costTaxMode,
            ],
            'metrics' => [
                'sale_price_incl' => $salePriceIncl,
                'sale_price_excl' => $salePriceExcl,
                'wholesale_price' => $wholesalePrice,
                'has_warnings' => $warnings !== [],
                'is_exportable' => ! $blockingWarnings,
            ],
        ];
    }

    public function defaultTaxRatePercent(): float
    {
        try {
            $value = Setting::query()
                ->where('key', 'prestashop_default_tax_rate')
                ->value('value');
        } catch (Throwable) {
            $value = null;
        }

        if ($value === null || $value === '') {
            return 21.0;
        }

        return max(0.0, (float) str_replace(',', '.', (string) $value));
    }

    public function defaultTaxRuleId(): int
    {
        try {
            $value = Setting::query()
                ->where('key', 'tax_rule_id')
                ->value('value');
        } catch (Throwable) {
            $value = null;
        }

        return max(1, (int) ($value ?: 1));
    }

    protected function resolveSourceProduct(MasterProduct $masterProduct): ?NormalizedProduct
    {
        $primarySupplier = $masterProduct->relationLoaded('masterProductSuppliers')
            ? $masterProduct->masterProductSuppliers->firstWhere('is_primary', true)
            : null;

        $primaryProduct = $primarySupplier?->relationLoaded('normalizedProduct')
            ? $primarySupplier->normalizedProduct
            : null;

        if ($primaryProduct instanceof NormalizedProduct) {
            return $primaryProduct;
        }

        if ($masterProduct->relationLoaded('normalizedProducts')) {
            return $masterProduct->normalizedProducts->first();
        }

        return $masterProduct->normalizedProducts()
            ->with(['supplier', 'supplierImport'])
            ->latest('id')
            ->first();
    }

    /**
     * @return array{mode: string, inferred: bool}
     */
    protected function resolveTaxInfo(?NormalizedProduct $product, string $field, string $fallback): array
    {
        if (! $product) {
            return ['mode' => $fallback, 'inferred' => true];
        }

        $import = null;
        if ($product->relationLoaded('supplierImport')) {
            $import = $product->supplierImport;
        } elseif ($product->supplier_import_id) {
            $import = SupplierImport::query()->find($product->supplier_import_id);
        }

        if (! $import) {
            return ['mode' => $fallback, 'inferred' => true];
        }

        if (! array_key_exists($import->id, $this->importSemanticsCache)) {
            $this->importSemanticsCache[$import->id] = $this->fieldSemanticsService->describeImport($import);
        }

        $mode = data_get($this->importSemanticsCache[$import->id], $field . '.tax_mode');

        if (in_array($mode, ['including_vat', 'excluding_vat'], true)) {
            return ['mode' => $mode, 'inferred' => false];
        }

        return ['mode' => $fallback, 'inferred' => true];
    }

    protected function normalizeNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    protected function formatDecimal(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format($value, 6, '.', '');
    }

    protected function loadedSupplierName(?NormalizedProduct $product): ?string
    {
        if (! $product || ! $product->relationLoaded('supplier')) {
            return null;
        }

        return $product->supplier?->name;
    }

    protected function loadedImportFilename(?NormalizedProduct $product): ?string
    {
        if (! $product || ! $product->relationLoaded('supplierImport')) {
            return null;
        }

        return $product->supplierImport?->filename_original;
    }
}
