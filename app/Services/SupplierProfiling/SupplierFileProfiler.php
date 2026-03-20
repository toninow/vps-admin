<?php

namespace App\Services\SupplierProfiling;

use App\Services\Import\CsvFileReader;
use App\Services\Import\FileTypeDetector;
use App\Services\Import\XlsxFileReader;
use App\Services\Import\XmlFileReader;
use RuntimeException;

class SupplierFileProfiler
{
    public function __construct(
        protected FileTypeDetector $detector,
        protected CsvFileReader $csvReader,
        protected XlsxFileReader $xlsxReader,
        protected XmlFileReader $xmlReader,
    ) {}

    public function profileDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new RuntimeException("Directorio no encontrado: {$directory}");
        }

        $files = scandir($directory) ?: [];
        $profiles = [];
        $globalSummary = [
            'total_files' => 0,
            'suppliers' => [],
        ];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) {
                continue;
            }

            $profile = $this->profileFile($path);
            if ($profile === null) {
                continue;
            }

            $profiles[] = $profile;
            $supplierKey = $profile['supplier']['code'] ?? 'desconocido';
            if (!isset($globalSummary['suppliers'][$supplierKey])) {
                $globalSummary['suppliers'][$supplierKey] = [
                    'files' => 0,
                    'total_rows' => 0,
                    'ean_columns_detected' => [],
                ];
            }
            $globalSummary['suppliers'][$supplierKey]['files']++;
            $globalSummary['suppliers'][$supplierKey]['total_rows'] += $profile['file']['rows'] ?? 0;
            foreach ($profile['ean_analysis']['candidates'] ?? [] as $candidate) {
                $col = $candidate['column'];
                if (!in_array($col, $globalSummary['suppliers'][$supplierKey]['ean_columns_detected'], true)) {
                    $globalSummary['suppliers'][$supplierKey]['ean_columns_detected'][] = $col;
                }
            }
            $globalSummary['total_files']++;
        }

        return [
            'files' => $profiles,
            'summary' => $globalSummary,
        ];
    }

    public function profileFile(string $path): ?array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls', 'xml'], true)) {
            return null;
        }

        $fileName = basename($path);
        $supplier = $this->inferSupplierFromFilename($fileName);

        $type = $this->detector->detectFromPath($path);
        $structure = [
            'path' => $path,
            'filename' => $fileName,
            'extension' => $extension,
            'detected_type' => $type,
            'sheets' => null,
            'rows' => 0,
            'columns' => [],
            'delimiter' => null,
            'encoding' => null,
        ];

        $rows = [];

        if ($type === FileTypeDetector::TYPE_CSV) {
            $reader = $this->csvReader;
            $meta = $reader->getFileMetadata($path);
            $structure['delimiter'] = $meta['delimiter'] ?? null;
            $structure['encoding'] = $meta['encoding'] ?? null;
            $structure['columns'] = $reader->getColumnNames($path);
            $rows = $reader->readRows($path, null);
            $structure['rows'] = count($rows);
        } elseif ($type === FileTypeDetector::TYPE_XLSX) {
            // Entorno sin ZipArchive u otros problemas de Excel no deben romper todo el profiling.
            try {
                $analysis = $this->xlsxReader->analyzeStructure($path, null);
                $structure['columns'] = $analysis['columns'] ?? [];
                $rows = $analysis['rows'] ?? [];
                $structure['rows'] = count($rows);
                $structure['sheets'] = array_map(function ($s) {
                    return [
                        'index' => $s['index'],
                        'name' => $s['name'],
                        'total_rows' => $s['total_rows'],
                        'total_columns' => $s['total_columns'],
                        'non_empty_rows_scanned' => $s['non_empty_rows_scanned'],
                        'sheet_score' => $s['sheet_score'],
                        'best_header_row' => $s['best_header_row'],
                    ];
                }, $analysis['sheets'] ?? []);
                $structure['selected_sheet'] = $analysis['selected_sheet_name'] ?? null;
                $structure['selected_sheet_index'] = $analysis['selected_sheet_index'] ?? null;
                $structure['selected_header_row'] = $analysis['selected_header_row'] ?? null;
            } catch (\Throwable $e) {
                $structure['error'] = 'No se pudo leer el archivo Excel para profiling: ' . $e->getMessage();
                // dejamos rows vacías; el resto del informe seguirá siendo útil
                $rows = [];
            }
        } elseif ($type === FileTypeDetector::TYPE_XML) {
            $reader = $this->xmlReader;
            $structure['columns'] = $reader->getColumnNames($path);
            $rows = $reader->readRows($path, null);
            $structure['rows'] = count($rows);
        } else {
            return null;
        }

        $columnAnalysis = $this->analyzeColumns($structure['columns'], $rows);
        $eanAnalysis = $this->analyzeEanCandidates($columnAnalysis['candidates']['ean'] ?? [], $rows);
        $categoryAnalysis = $this->analyzeCategories($columnAnalysis['candidates']['categories'] ?? [], $rows);
        $qualityAnalysis = $this->analyzeQuality($columnAnalysis, $rows);

        return [
            'file' => $structure,
            'supplier' => $supplier,
            'columns' => $columnAnalysis,
            'ean_analysis' => $eanAnalysis,
            'category_analysis' => $categoryAnalysis,
            'quality' => $qualityAnalysis,
        ];
    }

    protected function inferSupplierFromFilename(string $filename): array
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $clean = preg_replace('/\.(csv|txt|xlsx|xls|xml)$/i', '', $name);
        $parts = preg_split('/[_\-]+/', $clean) ?: [];
        $code = strtolower($parts[0] ?? $clean);

        return [
            'code' => $code,
            'raw' => $clean,
        ];
    }

    protected function analyzeColumns(array $columns, array $rows): array
    {
        $candidates = [
            'ean' => [],
            'name' => [],
            'brand' => [],
            'reference' => [],
            'supplier_reference' => [],
            'stock' => [],
            'cost_price' => [],
            'sale_price' => [],
            'description' => [],
            'categories' => [],
            'images' => [],
        ];

        foreach ($columns as $col) {
            $lower = mb_strtolower($col);
            if (preg_match('/ean|codigo[_ ]?ean|cod[-_ ]?barras|barcode/', $lower)) {
                $candidates['ean'][] = $col;
            }
            if (preg_match('/nombre|name|titulo|title|descripcion corta/', $lower)) {
                $candidates['name'][] = $col;
            }
            if (preg_match('/marca|brand|fabricante/', $lower)) {
                $candidates['brand'][] = $col;
            }
            if (preg_match('/ref(\b|erencia)|reference(?! price)|modelo|sku/', $lower)) {
                $candidates['reference'][] = $col;
            }
            if (preg_match('/ref[_ ]?proveedor|supplier[_ ]?ref|ref[_ ]?fab/', $lower)) {
                $candidates['supplier_reference'][] = $col;
            }
            if (preg_match('/stock|qty|cantidad|existencias/', $lower)) {
                $candidates['stock'][] = $col;
            }
            if (preg_match('/coste|costo|cost_price|precio[_ ]?compra|precio[_ ]?distribuidor|precio[_ ]?de[_ ]?coste|pre[_ ]?neto|neto|standard[_ ]?trade|retail(er)?/', $lower)) {
                $candidates['cost_price'][] = $col;
            }
            if (preg_match('/precio|pvp|pvpr|price|sale[_ ]?price|precio[_ ]?web|pvp[_ ]?con[_ ]?iva/', $lower)) {
                $candidates['sale_price'][] = $col;
            }
            if (preg_match('/descripcion|description|detalle/', $lower)) {
                $candidates['description'][] = $col;
            }
            if (preg_match('/categoria|category|familia|seccion/', $lower)) {
                $candidates['categories'][] = $col;
            }
            if (preg_match('/imagen|image|foto|url[_ ]?img|picture/', $lower)) {
                $candidates['images'][] = $col;
            }
        }

        return [
            'all' => $columns,
            'candidates' => $candidates,
        ];
    }

    protected function analyzeEanCandidates(array $eanColumns, array $rows): array
    {
        $result = [
            'candidates' => [],
            'notes' => [],
        ];

        foreach ($eanColumns as $col) {
            $stats = [
                'column' => $col,
                'total_rows' => 0,
                'non_empty' => 0,
                'empty' => 0,
                'numeric_only' => 0,
                'len_distribution' => [],
                'ean13' => 0,
                'upc12' => 0,
                'ean8' => 0,
                'possible_padded' => 0,
                'examples_valid' => [],
                'examples_invalid' => [],
            ];

            foreach ($rows as $row) {
                if (!array_key_exists($col, $row)) {
                    continue;
                }
                $stats['total_rows']++;
                $raw = (string) $row[$col];
                $value = trim($raw);
                if ($value === '') {
                    $stats['empty']++;
                    continue;
                }
                $stats['non_empty']++;

                $digits = preg_replace('/\D/', '', $value);
                $len = strlen($digits);
                $stats['len_distribution'][$len] = ($stats['len_distribution'][$len] ?? 0) + 1;

                if ($digits === $value) {
                    $stats['numeric_only']++;
                }

                $isValidLike = false;
                if ($len === 13) {
                    $stats['ean13']++;
                    $isValidLike = true;
                } elseif ($len === 12) {
                    $stats['upc12']++;
                } elseif ($len === 8) {
                    $stats['ean8']++;
                }

                if ($len < 13 && $len >= 8 && $digits === $value) {
                    $stats['possible_padded']++;
                }

                if ($isValidLike && count($stats['examples_valid']) < 5) {
                    $stats['examples_valid'][] = $value;
                } elseif (!$isValidLike && count($stats['examples_invalid']) < 5) {
                    $stats['examples_invalid'][] = $value;
                }
            }

            $result['candidates'][] = $stats;
        }

        return $result;
    }

    protected function analyzeCategories(array $categoryColumns, array $rows): array
    {
        $result = [
            'columns' => $categoryColumns,
            'samples' => [],
            'keywords' => [],
        ];

        foreach ($categoryColumns as $col) {
            $values = [];
            foreach ($rows as $row) {
                if (!array_key_exists($col, $row)) {
                    continue;
                }
                $val = trim((string) $row[$col]);
                if ($val !== '') {
                    $values[] = $val;
                }
                if (count($values) >= 50) {
                    break;
                }
            }

            $samples = array_slice(array_values(array_unique($values)), 0, 10);
            $result['samples'][$col] = $samples;

            $words = [];
            foreach ($samples as $sample) {
                $tokens = preg_split('/[>\|\-\/\\\\]+|\s+/', mb_strtolower($sample)) ?: [];
                foreach ($tokens as $t) {
                    $t = trim($t);
                    if ($t === '' || mb_strlen($t) < 3) {
                        continue;
                    }
                    $words[$t] = ($words[$t] ?? 0) + 1;
                }
            }
            arsort($words);
            $result['keywords'][$col] = array_slice($words, 0, 20, true);
        }

        return $result;
    }

    protected function analyzeQuality(array $columnAnalysis, array $rows): array
    {
        $candidates = $columnAnalysis['candidates'];
        $stats = [
            'name_completeness' => null,
            'brand_completeness' => null,
            'reference_completeness' => null,
            'description_completeness' => null,
            'image_url_completeness' => null,
            'rows_without_critical_data' => 0,
            'total_rows' => count($rows),
            'duplicate_ean_within_file' => 0,
        ];

        $firstCol = fn (array $list) => $list[0] ?? null;

        $nameCol = $firstCol($candidates['name']);
        $brandCol = $firstCol($candidates['brand']);
        $refCol = $firstCol($candidates['reference']);
        $descCol = $firstCol($candidates['description']);
        $imgCol = $firstCol($candidates['images']);
        $eanCol = $firstCol($candidates['ean']);

        $counters = [
            'name' => 0,
            'brand' => 0,
            'reference' => 0,
            'description' => 0,
            'image' => 0,
        ];

        $eanSeen = [];

        foreach ($rows as $row) {
            $hasCritical = true;

            if ($nameCol && !empty(trim((string) ($row[$nameCol] ?? '')))) {
                $counters['name']++;
            } else {
                $hasCritical = false;
            }

            if ($brandCol && !empty(trim((string) ($row[$brandCol] ?? '')))) {
                $counters['brand']++;
            }

            if ($refCol && !empty(trim((string) ($row[$refCol] ?? '')))) {
                $counters['reference']++;
            }

            if ($descCol && !empty(trim((string) ($row[$descCol] ?? '')))) {
                $counters['description']++;
            }

            if ($imgCol && !empty(trim((string) ($row[$imgCol] ?? '')))) {
                $counters['image']++;
            }

            if (!$hasCritical) {
                $stats['rows_without_critical_data']++;
            }

            if ($eanCol && isset($row[$eanCol])) {
                $digits = preg_replace('/\D/', '', (string) $row[$eanCol]);
                if ($digits !== '') {
                    $eanSeen[$digits] = ($eanSeen[$digits] ?? 0) + 1;
                }
            }
        }

        if ($stats['total_rows'] > 0) {
            $stats['name_completeness'] = round($counters['name'] / $stats['total_rows'] * 100, 2);
            $stats['brand_completeness'] = $brandCol ? round($counters['brand'] / $stats['total_rows'] * 100, 2) : null;
            $stats['reference_completeness'] = $refCol ? round($counters['reference'] / $stats['total_rows'] * 100, 2) : null;
            $stats['description_completeness'] = $descCol ? round($counters['description'] / $stats['total_rows'] * 100, 2) : null;
            $stats['image_url_completeness'] = $imgCol ? round($counters['image'] / $stats['total_rows'] * 100, 2) : null;
        }

        foreach ($eanSeen as $ean => $count) {
            if ($count > 1) {
                $stats['duplicate_ean_within_file'] += $count;
            }
        }

        return $stats;
    }
}
