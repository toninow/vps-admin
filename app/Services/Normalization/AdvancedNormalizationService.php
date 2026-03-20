<?php

namespace App\Services\Normalization;

use App\Models\NormalizedProduct;
use App\Support\CategoryPathFormatter;

class AdvancedNormalizationService
{
    /**
     * Aplica normalización avanzada a los productos normalizados indicados.
     * No modifica quantity (stock origen).
     */
    public function __construct(
        protected ProductTextFormatterService $textFormatter,
    ) {
    }

    public function normalize(array $normalizedProductIds): array
    {
        $updated = 0;
        $products = NormalizedProduct::with(['supplier', 'supplierImportRow'])
            ->whereIn('id', $normalizedProductIds)
            ->get();

        foreach ($products as $product) {
            $changed = false;
            $supplierRef = $this->normalizeSupplierReference($product->supplier_reference ?? '');
            if ($supplierRef !== (string) $product->supplier_reference) {
                $product->supplier_reference = $supplierRef ?: null;
                $changed = true;
            }

            $name = $this->normalizeName(
                $product->name ?? '',
                $product->brand ?? '',
                $supplierRef,
                $product->summary ?? '',
                $product->supplier?->slug,
                is_array($product->supplierImportRow?->raw_data) ? $product->supplierImportRow->raw_data : null
            );
            if ($name !== $product->name) {
                $product->name = $name;
                $changed = true;
            }

            $summary = $this->textFormatter->cleanSummaryForDisplay(
                $product->summary ?? '',
                $supplierRef ?? '',
                $name
            );
            if ($summary !== (string) $product->summary) {
                $product->summary = $summary ?: null;
                $changed = true;
            }

            $description = $this->normalizeDescription($product->description ?? '', $name);
            if ($description !== (string) $product->description) {
                $product->description = $description ?: null;
                $changed = true;
            }

            $brand = $this->normalizeBrand($product->brand ?? '');
            if ($brand !== (string) $product->brand) {
                $product->brand = $brand ?: null;
                $changed = true;
            }

            $categoryPath = CategoryPathFormatter::normalizeForStorage(
                (string) ($product->category_path_export ?? ''),
                $name,
                $summary
            );
            if (($categoryPath ?? null) !== ($product->category_path_export ?? null)) {
                $product->category_path_export = $categoryPath;
                $changed = true;
            }

            $generatedTags = $this->textFormatter->buildTags(
                $name,
                $brand,
                $supplierRef,
                $summary,
                $categoryPath ?? '',
                $description
            );
            $tags = $this->normalizeTags($generatedTags ?: ($product->tags ?? ''));
            if ($tags !== (string) $product->tags) {
                $product->tags = $tags ?: null;
                $changed = true;
            }

            $price = $this->normalizePrice($product->price_tax_incl);
            if ($price !== null && $price <= 0) {
                $price = null;
            }
            if ($price !== $product->price_tax_incl) {
                $product->price_tax_incl = $price;
                $changed = true;
            }

            $cost = $this->normalizePrice($product->cost_price);
            if ($cost !== null && $cost <= 0) {
                $cost = null;
            }
            if ($cost !== $product->cost_price) {
                $product->cost_price = $cost;
                $changed = true;
            }

            if ($changed) {
                $product->save();
                $updated++;
            }
        }

        return ['updated' => $updated, 'total' => $products->count()];
    }

    public function normalizeName(
        string $value,
        ?string $brand = null,
        ?string $supplierReference = null,
        ?string $summary = null,
        ?string $supplierSlug = null,
        ?array $rawData = null
    ): string
    {
        return $this->textFormatter->buildNormalizedName($value, $brand, $supplierReference, $summary, $supplierSlug, $rawData);
    }

    public function normalizeSummary(string $value, string $fallbackName = ''): string
    {
        return $this->textFormatter->buildSummary($value, $fallbackName);
    }

    public function normalizeDescription(string $value, string $fallbackName = ''): string
    {
        return $this->textFormatter->formatCharacteristics($value, $fallbackName);
    }

    public function normalizeBrand(string $value): string
    {
        $value = $this->stripHtml($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }

    public function normalizeSupplierReference(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value);
        return trim($value);
    }

    public function normalizeTags(string $value): string
    {
        $value = $this->stripHtml($value);
        $tags = array_filter(array_map('trim', preg_split('/[,;|]+/u', $value)));
        $tags = array_unique($tags);
        return implode(',', $tags);
    }

    public function normalizePrice($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        $raw = preg_replace('/[^\d,\.\-+eE]/u', '', $raw) ?? '';
        if ($raw === '') {
            return null;
        }

        if (preg_match('/e[+-]?\d+$/i', $raw)) {
            $scientific = str_replace(',', '.', $raw);

            return is_numeric($scientific) ? round((float) $scientific, 4) : null;
        }

        $commaCount = substr_count($raw, ',');
        $dotCount = substr_count($raw, '.');

        if ($commaCount === 0 && $dotCount > 0 && preg_match('/^\d{1,3}(?:\.\d{3})+$/', $raw)) {
            $raw = str_replace('.', '', $raw);
        }

        if ($commaCount > 0 && $dotCount > 0) {
            $decimalSeparator = strrpos($raw, ',') > strrpos($raw, '.') ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $raw = str_replace($thousandsSeparator, '', $raw);
            if ($decimalSeparator === ',') {
                $raw = str_replace(',', '.', $raw);
            }
        } elseif ($commaCount > 0) {
            if ($commaCount === 1 && preg_match('/,\d{1,4}$/', $raw)) {
                $raw = str_replace(',', '.', $raw);
            } else {
                $parts = explode(',', $raw);
                $last = array_pop($parts);
                $raw = implode('', $parts) . ($last !== null ? '.' . $last : '');
            }
        } elseif ($dotCount > 1) {
            $parts = explode('.', $raw);
            $last = array_pop($parts);
            $raw = implode('', $parts) . ($last !== null ? '.' . $last : '');
        } elseif ($dotCount === 1 && preg_match('/^\d+\.\d{3}$/', $raw)) {
            $raw = str_replace('.', '', $raw);
        }

        return is_numeric($raw) ? round((float) $raw, 4) : null;
    }

    protected function stripHtml(string $value): string
    {
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $value;
    }
}
