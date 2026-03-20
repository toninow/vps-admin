<?php

namespace App\Services\Import;

use App\Models\SupplierImport;

class ImportFieldSemanticsService
{
    /**
     * @return array<string, array<string, string|null>>
     */
    public function describeImport(SupplierImport $import): array
    {
        $columnsMap = data_get($import->mapping_snapshot, 'columns_map', []);

        return [
            'ean13' => $this->describeBarcodeField($columnsMap['ean13'] ?? null),
            'price_tax_incl' => $this->describePriceField($columnsMap['price_tax_incl'] ?? null, 'sale'),
            'cost_price' => $this->describePriceField($columnsMap['cost_price'] ?? null, 'cost'),
            'supplier_reference' => $this->describeReferenceField($columnsMap['supplier_reference'] ?? null),
            'category_path_export' => $this->describeCategoryField($columnsMap['category_path_export'] ?? null),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function describeBarcodeField(?string $sourceField): array
    {
        $normalized = $this->normalize($sourceField);

        $kind = 'codigo_interno';
        $label = 'Código interno o referencia';
        if ($this->containsAny($normalized, ['upc'])) {
            $kind = 'upc';
            $label = 'UPC / GTIN-12';
        } elseif ($this->containsAny($normalized, ['ean', 'gtin', 'barcode', 'codigo barras', 'codigo de barras'])) {
            $kind = 'ean';
            $label = 'EAN / GTIN';
        }

        return [
            'source' => $sourceField,
            'kind' => $kind,
            'label' => $label,
            'tax_mode' => null,
            'note' => $kind === 'ean'
                ? 'Si el origen llega en notación científica o con separadores, el sistema lo convierte antes de validar checksum.'
                : 'Si no parece un campo EAN/GTIN real, se conserva como código interno y no se fuerza como EAN.',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function describePriceField(?string $sourceField, string $role): array
    {
        $normalized = $this->normalize($sourceField);

        $roleLabel = $role === 'cost' ? 'Compra' : 'Venta';
        $taxMode = 'unknown';
        $taxLabel = 'IVA no deducido por cabecera';

        if ($this->containsAny($normalized, ['sin iva', 'excl vat', 'excl. vat', 'excluding vat', 'neto', 'net', 'ht', 'hors taxe', 'trade', 'coste', 'cost', 'distribuidor', 'einkauf'])) {
            $taxMode = 'excluding_vat';
            $taxLabel = 'Sin IVA detectado';
        } elseif ($this->containsAny($normalized, ['con iva', 'incl vat', 'incl. vat', 'iva incl', 'ttc', 'bruttopreis'])) {
            $taxMode = 'including_vat';
            $taxLabel = 'Con IVA detectado';
        }

        return [
            'source' => $sourceField,
            'kind' => $role,
            'label' => $roleLabel,
            'tax_mode' => $taxMode,
            'note' => $sourceField
                ? $roleLabel . ' tomada de `' . $sourceField . '` · ' . $taxLabel . '.'
                : 'Sin columna detectada.',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function describeReferenceField(?string $sourceField): array
    {
        return [
            'source' => $sourceField,
            'kind' => 'reference',
            'label' => 'Referencia proveedor',
            'tax_mode' => null,
            'note' => $sourceField ? 'Se usa como referencia comercial e identificador alternativo cuando no hay EAN válido.' : 'Sin referencia detectada.',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function describeCategoryField(?string $sourceField): array
    {
        $normalized = $this->normalize($sourceField);
        $kind = $this->containsAny($normalized, ['codigo', 'code']) ? 'coded_category' : 'text_category';

        return [
            'source' => $sourceField,
            'kind' => $kind,
            'label' => $kind === 'coded_category' ? 'Ruta/categoría codificada' : 'Ruta/categoría textual',
            'tax_mode' => null,
            'note' => $sourceField ? 'Se usa como base para sugerir la ruta final de categorías.' : 'Sin categoría origen detectada.',
        ];
    }

    protected function normalize(?string $value): string
    {
        if (! $value) {
            return '';
        }

        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = str_replace(['º', 'ª'], ['o', 'a'], $value);
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  string[]  $needles
     */
    protected function containsAny(string $haystack, array $needles): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
