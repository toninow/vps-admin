<?php

namespace App\Services\Normalization;

use App\Models\NormalizedProduct;
use App\Models\ProductEanIssue;

class EanIssueService
{
    public const TYPE_EMPTY = 'empty';

    public const TYPE_INVALID_LENGTH = 'invalid_length';

    public const TYPE_INVALID_CHARS = 'invalid_chars';

    public const TYPE_INVALID_CHECKSUM = 'invalid_checksum';

    public const TYPE_UPC_OR_OTHER = 'upc_or_other';

    public const EAN13_LENGTH = 13;

    /**
     * Detecta incidencias EAN y crea/actualiza product_ean_issues y ean_status en normalized_products.
     */
    public function detectAndRecordIssues(array $normalizedProductIds): array
    {
        $created = 0;
        $products = NormalizedProduct::with('productEanIssues')->whereIn('id', $normalizedProductIds)->get();

        foreach ($products as $product) {
            $value = $this->extractComparableValue($product);
            $barcodeStatus = strtolower(trim((string) ($product->barcode_status ?? '')));
            $barcodeType = strtolower(trim((string) ($product->barcode_type ?? '')));
            $issueType = $this->classifyProductIssue($product, $value, $barcodeStatus, $barcodeType);

            $product->ean_status = $this->resolveEanStatus($issueType, $barcodeStatus, $barcodeType);
            $product->save();

            if ($issueType === null) {
                $this->resolveExistingIssues($product);
                continue;
            }

            $existing = $product->productEanIssues()->whereNull('resolved_at')->first();
            if ($existing) {
                if ($existing->issue_type !== $issueType || $existing->value_received !== $value) {
                    $existing->update(['issue_type' => $issueType, 'value_received' => $value ?: null]);
                }
                continue;
            }

            ProductEanIssue::create([
                'normalized_product_id' => $product->id,
                'master_product_id' => $product->master_product_id,
                'issue_type' => $issueType,
                'value_received' => $value ?: null,
            ]);
            $created++;
        }

        return ['issues_created' => $created, 'total_checked' => $products->count()];
    }

    protected function classifyProductIssue(
        NormalizedProduct $product,
        string $value,
        string $barcodeStatus,
        string $barcodeType
    ): ?string {
        if ($barcodeStatus === 'non_ean' || in_array($barcodeType, ['upc12', 'gtin8', 'sku'], true)) {
            return null;
        }

        if ($barcodeStatus === 'missing') {
            return self::TYPE_EMPTY;
        }

        if ($barcodeStatus === 'invalid_ean' || str_starts_with((string) ($product->ean_status ?? ''), 'invalid_')) {
            return $this->classifyEan($value);
        }

        return $this->classifyEan($value);
    }

    /**
     * Clasifica el valor: null = válido EAN13, string = tipo de incidencia.
     */
    public function classifyEan(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return self::TYPE_EMPTY;
        }

        $digitsOnly = preg_replace('/\D/', '', $value);
        if ($digitsOnly === '') {
            return self::TYPE_INVALID_CHARS;
        }

        $len = strlen($digitsOnly);

        if ($len !== self::EAN13_LENGTH) {
            if ($len === 12 && str_starts_with($digitsOnly, '0') === false) {
                return self::TYPE_UPC_OR_OTHER;
            }
            if ($len === 8) {
                return self::TYPE_UPC_OR_OTHER;
            }
            return self::TYPE_INVALID_LENGTH;
        }

        if (preg_match('/\D/', $value)) {
            return self::TYPE_INVALID_CHARS;
        }

        if (! $this->validateEan13Checksum($digitsOnly)) {
            return self::TYPE_INVALID_CHECKSUM;
        }

        return null;
    }

    public function isValidEan13(string $value): bool
    {
        return $this->classifyEan($value) === null;
    }

    protected function resolveEanStatus(?string $issueType, string $barcodeStatus, string $barcodeType): ?string
    {
        if ($issueType === null) {
            if ($barcodeStatus === 'non_ean') {
                return $barcodeType !== '' ? $barcodeType : self::TYPE_UPC_OR_OTHER;
            }

            if ($barcodeStatus === 'missing') {
                return self::TYPE_EMPTY;
            }

            return 'ok';
        }

        return $issueType;
    }

    protected function validateEan13Checksum(string $digits): bool
    {
        if (strlen($digits) !== 13) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * (($i % 2) === 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        return (int) $digits[12] === $check;
    }

    protected function resolveExistingIssues(NormalizedProduct $product): void
    {
        $product->productEanIssues()->whereNull('resolved_at')->update(['resolved_at' => now()]);
    }

    protected function extractComparableValue(NormalizedProduct $product): string
    {
        $ean13 = trim((string) ($product->ean13 ?? ''));
        if ($ean13 !== '') {
            return $ean13;
        }

        return trim((string) ($product->barcode_raw ?? ''));
    }
}
