<?php

namespace App\Services\Import;

use App\Models\NormalizedProduct;

class BarcodeClassifierService
{
    /**
     * Clasifica un código de barras (EAN/UPC/GTIN u otros) a partir del valor crudo y del contexto.
     *
     * @param  string|null  $rawValue    Valor original (por ejemplo, columna EAN o SKU).
     * @param  string|null  $sourceField Nombre de la columna origen (ccgean, N.º UPC, sku, etc.).
     * @param  NormalizedProduct|null  $existing  (opcional) producto ya normalizado, para combinar con supplier_reference.
     * @return array{
     *   ean13: ?string,
     *   barcode_raw: ?string,
     *   barcode_type: ?string,
     *   barcode_status: ?string,
     *   ean_status: ?string
     * }
     */
    public function classify(?string $rawValue, ?string $sourceField = null, ?NormalizedProduct $existing = null): array
    {
        $raw = $rawValue !== null ? trim($rawValue) : '';
        if ($raw === '') {
            return [
                'ean13' => null,
                'barcode_raw' => null,
                'barcode_type' => 'none',
                'barcode_status' => 'missing',
                'ean_status' => null,
            ];
        }

        $normalizedBarcodeValue = $this->normalizeBarcodeValue($raw);
        $digits = preg_replace('/\D/', '', $normalizedBarcodeValue) ?? '';
        $digits = trim($digits);
        $len = strlen($digits);
        $barcodeRaw = $normalizedBarcodeValue;

        $normalizedSource = $this->normalizeSourceField($sourceField);
        $isEanLikeSource = $this->looksLikeEanSource($normalizedSource);

        // Primero, intentar clasificar como código de barras estándar por longitud,
        // pero solo si la columna origen parece realmente un campo de EAN/UPC/GTIN.
        if ($isEanLikeSource && $digits !== '') {
            if ($len === 13) {
                return [
                    'ean13' => $this->validateEan13Checksum($digits) ? $digits : null,
                    'barcode_raw' => $barcodeRaw,
                    'barcode_type' => 'ean13',
                    'barcode_status' => $this->validateEan13Checksum($digits) ? 'ok' : 'invalid_ean',
                    'ean_status' => $this->validateEan13Checksum($digits) ? 'ok' : 'invalid_checksum',
                ];
            }

            // Algunos proveedores entregan GTIN-14 con un 0 inicial; si al quitarlo queda un EAN13 válido, lo recuperamos.
            if ($len === 14 && str_starts_with($digits, '0')) {
                $candidate = substr($digits, 1);
                if ($this->validateEan13Checksum($candidate)) {
                    return [
                        'ean13' => $candidate,
                        'barcode_raw' => $barcodeRaw,
                        'barcode_type' => 'ean13',
                        'barcode_status' => 'ok',
                        'ean_status' => 'ok',
                    ];
                }
            }

            if ($len === 12 && $this->shouldPrefixZeroForTwelveDigits($normalizedSource)) {
                $candidate = '0' . $digits;
                $isValidCandidate = $this->validateEan13Checksum($candidate);

                return [
                    'ean13' => $isValidCandidate ? $candidate : null,
                    'barcode_raw' => $barcodeRaw,
                    'barcode_type' => 'ean13',
                    'barcode_status' => $isValidCandidate ? 'ok' : 'invalid_ean',
                    'ean_status' => $isValidCandidate ? 'ok' : 'invalid_checksum',
                ];
            }

            if ($len === 12) {
                return [
                    'ean13' => null,
                    'barcode_raw' => $barcodeRaw,
                    'barcode_type' => 'upc12',
                    'barcode_status' => 'non_ean',
                    'ean_status' => null,
                ];
            }

            if ($len === 8) {
                return [
                    'ean13' => null,
                    'barcode_raw' => $barcodeRaw,
                    'barcode_type' => 'gtin8',
                    'barcode_status' => 'non_ean',
                    'ean_status' => null,
                ];
            }
        }

        // Si la columna parece de EAN pero la longitud NO es válida, es un error real de EAN.
        if ($isEanLikeSource) {
            return [
                'ean13' => null,
                'barcode_raw' => $barcodeRaw,
                'barcode_type' => 'unknown',
                'barcode_status' => 'invalid_ean',
                'ean_status' => $len > 0 ? 'invalid_length_' . $len : 'invalid_length',
            ];
        }

        // Si no encaja como EAN/UPC/GTIN y la columna NO es EAN-like,
        // lo tratamos como código interno / proveedor, NO como EAN inválido.
        $barcodeType = $this->inferInternalTypeFromSource($normalizedSource);

        return [
            'ean13' => null,
            'barcode_raw' => $barcodeRaw,
            'barcode_type' => $barcodeType,
            'barcode_status' => 'non_ean',
            'ean_status' => null,
        ];
    }

    protected function normalizeSourceField(?string $sourceField): string
    {
        if (! $sourceField) {
            return '';
        }
        $s = mb_strtolower(trim($sourceField), 'UTF-8');
        $s = $this->removeAccents($s);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    protected function inferInternalTypeFromSource(string $normalizedSource): string
    {
        if ($normalizedSource === '') {
            return 'unknown';
        }

        // supplier reference
        if (str_contains($normalizedSource, 'supplier ref')
            || str_contains($normalizedSource, 'ref proveedor')
            || str_contains($normalizedSource, 'referencia proveedor')) {
            return 'supplier_reference';
        }

        // sku
        if (str_contains($normalizedSource, 'sku')) {
            return 'sku';
        }

        // model number
        if (str_contains($normalizedSource, 'model number')
            || str_contains($normalizedSource, 'numero de modelo')
            || str_contains($normalizedSource, 'número de modelo')) {
            return 'model_number';
        }

        // article / artikelnummer
        if (str_contains($normalizedSource, 'artikelnummer')
            || str_contains($normalizedSource, 'artikel nr')
            || str_contains($normalizedSource, 'article number')
            || str_contains($normalizedSource, 'numero de articulo')
            || str_contains($normalizedSource, 'número de artículo')) {
            return 'article_number';
        }

        // GEWA style internal codes (ccgart, ccgartdot, etc.)
        if (str_contains($normalizedSource, 'ccgart') || str_contains($normalizedSource, 'artnr')) {
            return 'internal_supplier_code';
        }

        return 'unknown';
    }

    protected function looksLikeEanSource(string $normalizedSource): bool
    {
        if ($normalizedSource === '') {
            return false;
        }

        // Palabras clave típicas de columnas EAN/GTIN/UPC/barcode
        $needles = ['ean', 'gtin', 'upc', 'barcode', 'codigo barras', 'codigo de barras'];
        foreach ($needles as $n) {
            if (str_contains($normalizedSource, $n)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldPrefixZeroForTwelveDigits(string $normalizedSource): bool
    {
        if ($normalizedSource === '') {
            return false;
        }

        if (str_contains($normalizedSource, 'upc')) {
            return false;
        }

        return str_contains($normalizedSource, 'ean');
    }

    protected function validateEan13Checksum(string $digits): bool
    {
        if (strlen($digits) !== 13 || preg_match('/\D/', $digits)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * (($i % 2) === 0 ? 1 : 3);
        }

        $check = (10 - ($sum % 10)) % 10;

        return (int) $digits[12] === $check;
    }

    protected function normalizeBarcodeValue(string $raw): string
    {
        $expandedScientific = $this->expandScientificNotationInteger($raw);
        if ($expandedScientific !== null) {
            return $expandedScientific;
        }

        $numericWithSeparators = $this->normalizeNumericBarcodeWithSeparators($raw);

        return $numericWithSeparators ?? $raw;
    }

    protected function expandScientificNotationInteger(string $raw): ?string
    {
        $candidate = preg_replace('/\s+/u', '', trim($raw)) ?? '';
        if ($candidate === '') {
            return null;
        }

        if (! preg_match('/^[+]?(\d+(?:[.,]\d+)?)e([+-]?\d+)$/i', $candidate, $matches)) {
            return null;
        }

        $mantissa = str_replace(',', '.', $matches[1]);
        $exponent = (int) $matches[2];
        if ($exponent < 0) {
            return null;
        }

        [$integerPart, $fractionPart] = array_pad(explode('.', $mantissa, 2), 2, '');
        $integerPart = preg_replace('/\D/', '', $integerPart) ?? '';
        $fractionPart = preg_replace('/\D/', '', $fractionPart) ?? '';

        if ($integerPart === '' && $fractionPart === '') {
            return null;
        }

        $allDigits = $integerPart . $fractionPart;
        $decimalPosition = strlen($integerPart) + $exponent;

        if ($decimalPosition < 0) {
            return null;
        }

        if ($decimalPosition >= strlen($allDigits)) {
            $integerValue = $allDigits . str_repeat('0', $decimalPosition - strlen($allDigits));

            return ltrim($integerValue, '0') ?: '0';
        }

        $integerValue = substr($allDigits, 0, $decimalPosition);
        $fractionValue = substr($allDigits, $decimalPosition);
        if (rtrim($fractionValue, '0') !== '') {
            return null;
        }

        return ltrim($integerValue, '0') ?: '0';
    }

    protected function normalizeNumericBarcodeWithSeparators(string $raw): ?string
    {
        $candidate = trim($raw);
        if ($candidate === '') {
            return null;
        }

        if (! preg_match('/^\d[\d\s,\.\/]*\d$/', $candidate)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $candidate) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function removeAccents(string $s): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];

        return strtr($s, $map);
    }
}
