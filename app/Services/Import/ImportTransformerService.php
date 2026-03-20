<?php

namespace App\Services\Import;

use App\Models\NormalizedProduct;
use App\Models\SupplierImport;
use App\Models\SupplierImportRow;
use App\Services\Normalization\ProductTextFormatterService;
use App\Support\CategoryPathFormatter;

class ImportTransformerService
{
    protected const NON_PRODUCT_MARKERS = [
        'p-compra',
        'portes de compra',
        'portes compra',
        'gastos de envio',
        'gastos envío',
        'shipping fee',
        'delivery fee',
        'transport charge',
    ];

    /**
     * Campos destino válidos para normalized_products.
     * quantity no se importa del proveedor: siempre 0 (stock lo introduce el usuario en la app).
     *
     * @var array<int, string>
     */
    public const TARGET_FIELDS = [
        'name',
        'summary',
        'description',
        'ean13',
        'quantity',
        'price_tax_incl',
        'cost_price',
        'brand',
        'category_path_export',
        'tags',
        'supplier_reference',
        'image_urls',
    ];

    /**
     * Transforma filas pendientes usando mapping_snapshot y crea normalized_products.
     * quantity se deja siempre en 0; el stock no se importa del proveedor.
     */
    public function __construct(
        protected BarcodeClassifierService $barcodeClassifier,
        protected ProductTextFormatterService $textFormatter,
    ) {
    }

    public function transformImportToNormalizedProducts(SupplierImport $import, ?callable $progressCallback = null): array
    {
        $snapshot = $import->mapping_snapshot;
        if (empty($snapshot['columns_map']) || ! is_array($snapshot['columns_map'])) {
            return ['created' => 0, 'errors' => 0, 'skipped' => 0, 'messages' => ['No hay mapeo de columnas guardado.']];
        }

        // Default localizado SOLO para YAMAHA:
        // si `brand` viene vacío tras el mapping, asignar literal 'YAMAHA'.
        // (No afecta a otros proveedores.)
        $import->loadMissing('supplier');
        $yamahaSlug = $import->supplier?->slug === 'yamaha';

        $columnsMap = $snapshot['columns_map'];
        $supplierId = $import->supplier_id;
        $rowsQuery = $import->supplierImportRows()
            ->where('status', SupplierImportRow::STATUS_PENDING)
            ->orderBy('id');
        $totalRows = (clone $rowsQuery)->count();

        if ($totalRows === 0) {
            return ['created' => 0, 'errors' => 0, 'skipped' => 0, 'messages' => ['No hay filas pendientes de procesar.']];
        }

        $created = 0;
        $errors = 0;
        $skipped = 0;
        $handled = 0;
        $messages = [];

        $rowsQuery->chunkById(250, function ($rows) use (
            $columnsMap,
            &$created,
            &$errors,
            &$skipped,
            &$handled,
            &$messages,
            $yamahaSlug,
            $supplierId,
            $import,
            $progressCallback,
            $totalRows
        ) {
            foreach ($rows as $row) {
                $raw = $row->raw_data;
                if (! is_array($raw)) {
                    $row->update([
                        'status' => SupplierImportRow::STATUS_ERROR,
                        'error_message' => 'raw_data inválido o no es un array.',
                    ]);
                    $errors++;
                    $handled++;
                    $messages[] = "Fila {$row->row_index}: raw_data inválido.";
                    $this->reportProgress($progressCallback, $handled, $totalRows, $created, $errors, $skipped, 'Leyendo filas...');
                    continue;
                }

                $normalized = $this->applyMapping($raw, $columnsMap);
                $normalized = $this->applyNameFallbacks($normalized);
                $normalized = $this->sanitizeTextFields($normalized);
                $normalized = $this->sanitizeCategoryPath($normalized);
                $normalized = $this->applyPresentationDefaults($normalized, $raw, $import->supplier?->slug);

                if ($this->isNonProductRow($normalized, $raw)) {
                    $row->update([
                        'status' => SupplierImportRow::STATUS_SKIPPED,
                        'error_message' => 'Fila no producto omitida automáticamente (portes/gastos/servicio).',
                    ]);
                    $skipped++;
                    $handled++;
                    $messages[] = "Fila {$row->row_index}: omitida (línea no producto).";
                    $this->reportProgress($progressCallback, $handled, $totalRows, $created, $errors, $skipped, 'Filtrando líneas no producto...');
                    continue;
                }

                $name = trim((string) ($normalized['name'] ?? ''));
                if ($name === '') {
                    $row->update([
                        'status' => SupplierImportRow::STATUS_SKIPPED,
                        'error_message' => 'Nombre vacío; la fila se omite.',
                    ]);
                    $skipped++;
                    $handled++;
                    $messages[] = "Fila {$row->row_index}: omitida (nombre vacío).";
                    $this->reportProgress($progressCallback, $handled, $totalRows, $created, $errors, $skipped, 'Aplicando reglas de presentación...');
                    continue;
                }

                $normalized = $this->sanitizeNumericFields($normalized);

                if ($yamahaSlug) {
                    $brand = $normalized['brand'] ?? '';
                    if ($brand === null || trim((string) $brand) === '') {
                        $normalized['brand'] = 'YAMAHA';
                    }
                }

                $barcodeCandidate = $this->findPrimaryBarcodeCandidate($normalized, $columnsMap);
                $barcodeData = $this->barcodeClassifier->classify(
                    $barcodeCandidate['rawValue'],
                    $barcodeCandidate['sourceField'],
                );

                $normalized['ean13'] = $barcodeData['ean13'];
                $normalized['barcode_raw'] = $barcodeData['barcode_raw'];
                $normalized['barcode_type'] = $barcodeData['barcode_type'];
                $normalized['barcode_status'] = $barcodeData['barcode_status'];
                $normalized['ean_status'] = $barcodeData['ean_status'];

                $normalized['quantity'] = 0;
                $normalized['supplier_id'] = $supplierId;
                $normalized['supplier_import_id'] = $import->id;
                $normalized['supplier_import_row_id'] = $row->id;
                $normalized['master_product_id'] = null;
                $normalized['validation_status'] = 'pending';
                $normalized['warehouse'] = 'CARPETANA';
                $normalized['tax_rule_id'] = 1;
                $normalized['active'] = 1;

                try {
                    NormalizedProduct::create($normalized);
                    $row->update([
                        'normalized_data' => $normalized,
                        'status' => SupplierImportRow::STATUS_PROCESSED,
                        'error_message' => null,
                    ]);
                    $created++;
                } catch (\Throwable $e) {
                    $row->update([
                        'status' => SupplierImportRow::STATUS_ERROR,
                        'error_message' => $e->getMessage(),
                    ]);
                    $errors++;
                    $messages[] = "Fila {$row->row_index}: " . $e->getMessage();
                }

                $handled++;
                $this->reportProgress($progressCallback, $handled, $totalRows, $created, $errors, $skipped, 'Transformando filas a productos normalizados...');
            }
        }, 'id');

        $import->update([
            'processed_rows' => $created,
            'error_rows' => $errors,
            'status' => 'processed',
            'finished_at' => now(),
        ]);

        return ['created' => $created, 'errors' => $errors, 'skipped' => $skipped, 'messages' => $messages];
    }

    protected function reportProgress(
        ?callable $progressCallback,
        int $handled,
        int $total,
        int $created,
        int $errors,
        int $skipped,
        string $message
    ): void {
        if ($progressCallback === null) {
            return;
        }

        $progressCallback([
            'handled' => $handled,
            'total' => $total,
            'created' => $created,
            'errors' => $errors,
            'skipped' => $skipped,
            'message' => $message,
        ]);
    }

    /**
     * Evita perder filas útiles cuando el proveedor no separa bien nombre, resumen y descripción.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function applyNameFallbacks(array $normalized): array
    {
        $currentName = trim((string) ($normalized['name'] ?? ''));
        if ($currentName !== '') {
            return $normalized;
        }

        foreach (['summary', 'description', 'supplier_reference'] as $field) {
            $candidate = trim((string) ($normalized[$field] ?? ''));
            if ($candidate !== '') {
                $normalized['name'] = $candidate;
                return $normalized;
            }
        }

        return $normalized;
    }

    /**
     * Detecta filas que no representan productos reales del catálogo.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $raw
     */
    protected function isNonProductRow(array $normalized, array $raw): bool
    {
        $signals = [
            (string) ($normalized['name'] ?? ''),
            (string) ($normalized['summary'] ?? ''),
            (string) ($normalized['supplier_reference'] ?? ''),
            (string) ($normalized['description'] ?? ''),
            (string) ($raw['product_url'] ?? ''),
            (string) ($raw['reference'] ?? ''),
            (string) ($raw['name'] ?? ''),
        ];

        $haystack = mb_strtolower(implode(' | ', array_map(
            fn ($value) => $this->sanitizeSingleLineText($value, null, true),
            $signals
        )), 'UTF-8');

        $matchesMarker = false;
        foreach (self::NON_PRODUCT_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                $matchesMarker = true;
                break;
            }
        }

        if (! $matchesMarker) {
            return false;
        }

        $price = $normalized['price_tax_incl'] ?? null;
        $ean = trim((string) ($normalized['ean13'] ?? ''));
        $brand = trim((string) ($normalized['brand'] ?? ''));

        return ($price === null || (float) $price <= 0.0)
            && $ean === ''
            && $brand === '';
    }

    /**
     * Limpia campos textuales para evitar HTML residual, caracteres de control y desbordes en columnas string.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function sanitizeTextFields(array $normalized): array
    {
        $normalized['name'] = $this->sanitizeSingleLineText($normalized['name'] ?? '', 255, true);
        $normalized['summary'] = $this->sanitizeSingleLineText($normalized['summary'] ?? '', null, true);
        $normalized['supplier_reference'] = $this->sanitizeSingleLineText($normalized['supplier_reference'] ?? '', 255, true);
        $normalized['brand'] = $this->sanitizeSingleLineText($normalized['brand'] ?? '', 255, true);
        $normalized['category_path_export'] = $this->sanitizeSingleLineText($normalized['category_path_export'] ?? '', null, true);
        $normalized['tags'] = $this->sanitizeSingleLineText($normalized['tags'] ?? '', null, true);

        foreach (['description'] as $field) {
            $value = (string) ($normalized[$field] ?? '');
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? $value;
            $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
            $normalized[$field] = trim($value);
        }

        return $normalized;
    }

    /**
     * Evita usar nombres de producto como si fueran rutas de categoría y normaliza separadores a coma simple.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function sanitizeCategoryPath(array $normalized): array
    {
        $normalized['category_path_export'] = CategoryPathFormatter::normalizeForStorage(
            (string) ($normalized['category_path_export'] ?? ''),
            (string) ($normalized['name'] ?? ''),
            (string) ($normalized['summary'] ?? '')
        ) ?? '';

        return $normalized;
    }

    /**
     * Ajusta nombre comercial y resumen mínimo visible siguiendo reglas conservadoras.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function applyPresentationDefaults(array $normalized, array $raw = [], ?string $supplierSlug = null): array
    {
        $name = $this->textFormatter->buildNormalizedName(
            (string) ($normalized['name'] ?? ''),
            (string) ($normalized['brand'] ?? ''),
            (string) ($normalized['supplier_reference'] ?? ''),
            (string) ($normalized['summary'] ?? ''),
            $supplierSlug,
            $raw
        );

        $normalized['name'] = $name;
        $normalized['summary'] = $this->textFormatter->buildSummary(
            (string) ($normalized['summary'] ?? ''),
            $name
        );

        return $normalized;
    }

    protected function sanitizeSingleLineText(mixed $value, ?int $maxLength = null, bool $stripHtml = false): string
    {
        $value = (string) $value;
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($stripHtml) {
            $value = strip_tags($value);
        }
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $value = rtrim(mb_substr($value, 0, $maxLength));
        }

        return $value;
    }

    /**
     * Aplica el mapeo de columnas y transformaciones simples.
     *
     * @param  array<string, string>  $raw
     * @param  array<string, string>  $columnsMap  [ target_field => source_key ]
     * @return array<string, mixed>
     */
    protected function applyMapping(array $raw, array $columnsMap): array
    {
        $out = [];
        foreach (self::TARGET_FIELDS as $field) {
            if ($field === 'image_urls') {
                $out[$field] = [];
            } elseif ($field === 'quantity') {
                $out[$field] = 0;
            } elseif (in_array($field, ['price_tax_incl', 'cost_price'], true)) {
                $out[$field] = null;
            } else {
                $out[$field] = '';
            }
        }

        foreach ($columnsMap as $targetField => $sourceKey) {
            if (! in_array($targetField, self::TARGET_FIELDS, true)) {
                continue;
            }
            if ($targetField === 'quantity') {
                continue;
            }
            $value = $raw[$sourceKey] ?? $raw[trim((string) $sourceKey)] ?? '';
            $value = is_scalar($value) ? trim((string) $value) : '';
            $out[$targetField] = $this->transformValue($targetField, $value);
        }

        return $out;
    }

    protected function transformValue(string $targetField, string $value): mixed
    {
        if ($value === '') {
            if ($targetField === 'image_urls') {
                return [];
            }
            if (in_array($targetField, ['price_tax_incl', 'cost_price'], true)) {
                return null;
            }
            if ($targetField === 'ean13') {
                return null;
            }
            return '';
        }

        if ($targetField === 'quantity') {
            return 0;
        }

        if ($targetField === 'image_urls') {
            return $this->parseImageUrlsString($value);
        }

        if (in_array($targetField, ['price_tax_incl', 'cost_price'], true)) {
            return $this->parseDecimalValue($value);
        }

        if ($targetField === 'ean13') {
            // No normalizamos ni validamos aquí: la validación/clasificación central
            // la hace BarcodeClassifierService (incluyendo invalid_ean vs non_ean).
            // Devolvemos el valor tal cual (trim ya aplicado antes) para que barcode_raw
            // conserve el formato original si aplica.
            return $value;
        }

        return $value;
    }

    /**
     * Determina el candidato principal de código a clasificar (valor + nombre de columna origen).
     *
     * Prioridad:
     * 1) ean13 (si existe valor en normalized y hay columna mapeada)
     * 2) supplier_reference (si no hay ean13 válido, pero sí referencia proveedor)
     *
     * @param  array<string,mixed>        $normalized
     * @param  array<string,string>       $columnsMap
     * @return array{rawValue: ?string, sourceField: ?string}
     */
    protected function findPrimaryBarcodeCandidate(array $normalized, array $columnsMap): array
    {
        // 1) EAN/UPC/GTIN mapeado a ean13
        $eanValue = $normalized['ean13'] ?? null;
        if (is_string($eanValue) && trim($eanValue) !== '' && isset($columnsMap['ean13'])) {
            return [
                'rawValue' => $eanValue,
                'sourceField' => (string) $columnsMap['ean13'],
            ];
        }

        // 2) Referencia proveedor como código alternativo cuando no hay EAN/UPC/GTIN válido
        $supplierRef = $normalized['supplier_reference'] ?? null;
        if (is_string($supplierRef) && trim($supplierRef) !== '') {
            return [
                'rawValue' => $supplierRef,
                'sourceField' => $columnsMap['supplier_reference'] ?? 'supplier_reference',
            ];
        }

        // 3) Sin candidato claro
        return [
            'rawValue' => null,
            'sourceField' => null,
        ];
    }

    /**
     * Garantiza que nunca lleguen '' a columnas numéricas/decimales.
     *
     * Reglas:
     * - price_tax_incl, cost_price: '' => null
     * - quantity se fuerza siempre a 0 en el flujo principal
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function sanitizeNumericFields(array $normalized): array
    {
        foreach (['price_tax_incl', 'cost_price'] as $field) {
            if (array_key_exists($field, $normalized) && $normalized[$field] === '') {
                $normalized[$field] = null;
            }

            if (array_key_exists($field, $normalized) && is_numeric($normalized[$field]) && (float) $normalized[$field] <= 0.0) {
                $normalized[$field] = null;
            }
        }

        return $normalized;
    }

    protected function parseDecimalValue(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,\.\-+eE]/u', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        if (preg_match('/e[+-]?\d+$/i', $value)) {
            $scientific = str_replace(',', '.', $value);

            return is_numeric($scientific) ? round((float) $scientific, 4) : null;
        }

        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        if ($commaCount === 0 && $dotCount > 0 && preg_match('/^\d{1,3}(?:\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        if ($commaCount > 0 && $dotCount > 0) {
            $decimalSeparator = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandsSeparator, '', $value);
            if ($decimalSeparator === ',') {
                $value = str_replace(',', '.', $value);
            }
        } elseif ($commaCount > 0) {
            if ($commaCount === 1 && preg_match('/,\d{1,4}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $parts = explode(',', $value);
                $last = array_pop($parts);
                $value = implode('', $parts) . ($last !== null ? '.' . $last : '');
            }
        } elseif ($dotCount > 1) {
            $parts = explode('.', $value);
            $last = array_pop($parts);
            $value = implode('', $parts) . ($last !== null ? '.' . $last : '');
        } elseif ($dotCount === 1 && preg_match('/^\d+\.\d{3}$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? round((float) $value, 4) : null;
    }

    /**
     * Convierte string (una o varias URLs separadas por coma, pipe, etc.) en array de strings.
     *
     * @return array<int, string>
     */
    protected function parseImageUrlsString(string $value): array
    {
        $parts = preg_split('/[\s,;|\n\r]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }
        return $out;
    }
}
