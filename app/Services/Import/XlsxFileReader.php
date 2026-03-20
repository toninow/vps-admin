<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxFileReader implements FileReaderInterface
{
    /**
     * Cabeceras que consideramos "códigos" y deben preservarse como texto visible (ceros a la izquierda,
     * sin notación científica, sin convertir a fecha).
     *
     * @var array<int, string>
     */
    protected array $codeHeaderHints = [
        'ean', 'ean13', 'gtin', 'upc', 'barcode', 'codigo barras', 'código barras',
        // referencias/códigos
        'sku', 'ref', 'referencia', 'reference', 'supplier ref', 'supplier_reference',
        'cod articulo', 'codigo articulo', 'código artículo', 'codigo producto', 'código producto',
        'product code', 'item code', 'article code', 'article number', 'art number',
        // modelos / números internos
        'model', 'modelo', 'model number', 'numero de modelo', 'número de modelo',
        'part no', 'part number', 'item no', 'item number',
        // variantes idiomáticas
        'numero de articulo', 'número de artículo', 'artikelnummer', 'artikel nr', 'artnr', 'art nr',
        // columnas específicas (GEWA)
        'ccgart', 'ccgartdot',
    ];

    /**
     * Cabeceras que consideramos numéricas de importe (preferimos valor crudo, no formateado).
     *
     * @var array<int, string>
     */
    protected array $priceHeaderHints = [
        'precio', 'price', 'pvp', 'coste', 'costo', 'cost', 'net price', 'precio neto',
        'importe', 'amount', 'uvp', 'verkaufspreis', 'verkauf', 'einkaufspreis', 'einkauf',
        // columnas específicas (GEWA)
        'ccghev', 'ccgevk',
    ];

    /**
     * Devuelve solo los nombres de columna, usando la heurística de hoja/cabecera real.
     */
    public function getColumnNames(string $path): array
    {
        $analysis = $this->analyzeStructure($path, 0);
        return $analysis['columns'] ?? [];
    }

    /**
     * Devuelve las filas de datos usando la hoja y cabecera elegidas por la heurística.
     */
    public function readRows(string $path, ?int $limit = null): array
    {
        $analysis = $this->analyzeStructure($path, $limit);
        return $analysis['rows'] ?? [];
    }

    /**
     * Analiza todas las hojas y filas iniciales para detectar:
     * - hoja más probable de catálogo
     * - fila de cabecera real
     * - columnas y filas de datos
     * - puntuaciones de hoja y cabecera para diagnóstico.
     *
     * @return array{
     *   sheets: array<int, array>,
     *   selected_sheet_index:int|null,
     *   selected_sheet_name:string|null,
     *   selected_header_row:int|null,
     *   columns:array<int,string>,
     *   rows:array<int,array<string,mixed>>
     * }
     */
    public function analyzeStructure(string $path, ?int $limit = null): array
    {
        $this->ensureFileExists($path);
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo leer el archivo Excel: ' . $e->getMessage());
        }

        $sheetSummaries = [];
        $bestSheetIndex = null;
        $bestSheetScore = -1;

        foreach ($spreadsheet->getWorksheetIterator() as $index => $sheet) {
            $name = $sheet->getTitle();
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $nonEmptyRows = 0;
            $rowNonEmptyCounts = [];
            $maxRowsToScan = min($highestRow, 500);

            for ($r = 1; $r <= $maxRowsToScan; $r++) {
                $rowHasData = false;
                $count = 0;
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c) . $r;
                    $val = $sheet->getCell($coord)->getValue();
                    if ($val !== null && trim((string) $val) !== '') {
                        $rowHasData = true;
                        $count++;
                    }
                }
                if ($rowHasData) {
                    $nonEmptyRows++;
                }
                $rowNonEmptyCounts[$r] = $count;
            }

            // Buscar filas candidatas a cabecera en las primeras N filas con datos
            $headerCandidates = [];
            $maxHeaderRow = min($highestRow, 50);
            $keywords = ['ean', 'codigo', 'código', 'barras', 'barcode', 'nombre', 'name', 'titulo', 'title', 'marca', 'brand', 'ref', 'referencia', 'sku', 'stock', 'qty', 'cantidad', 'precio', 'price', 'pvp', 'descripcion', 'description', 'categoria', 'category', 'familia', 'image', 'imagen', 'foto'];

            for ($r = 1; $r <= $maxHeaderRow; $r++) {
                $nonEmpty = $rowNonEmptyCounts[$r] ?? 0;
                if ($nonEmpty === 0) {
                    continue;
                }

                $textCells = 0;
                $keywordHits = 0;
                $values = [];

                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c) . $r;
                    $val = $sheet->getCell($coord)->getValue();
                    if ($val === null) {
                        continue;
                    }
                    $valStr = trim((string) $val);
                    if ($valStr === '') {
                        continue;
                    }
                    $values[] = $valStr;
                    if (!is_numeric($valStr)) {
                        $textCells++;
                    }
                    $lower = mb_strtolower($valStr);
                    foreach ($keywords as $kw) {
                        if (str_contains($lower, $kw)) {
                            $keywordHits++;
                            break;
                        }
                    }
                }

                if ($nonEmpty === 0) {
                    continue;
                }

                // Mirar filas de datos debajo de esta fila para ver si parece cabecera
                $dataRowsBelow = 0;
                $maxDataCheck = min($highestRow, $r + 100);
                for ($rr = $r + 1; $rr <= $maxDataCheck; $rr++) {
                    $rowCount = $rowNonEmptyCounts[$rr] ?? 0;
                    if ($rowCount >= 2) {
                        $dataRowsBelow++;
                    }
                }

                $textRatio = $nonEmpty > 0 ? $textCells / $nonEmpty : 0.0;
                $score = 0.0;
                $score += $nonEmpty;               // más columnas no vacías
                $score += $textCells * 0.5;        // preferimos texto frente a números
                $score += $keywordHits * 3.0;      // fuerte peso a palabras típicas de cabecera
                $score += $dataRowsBelow * 0.7;    // muchas filas de datos debajo

                // Penalización si casi todo son números (probablemente datos, no cabecera)
                if ($textRatio < 0.3) {
                    $score *= 0.4;
                }

                $headerCandidates[] = [
                    'row' => $r,
                    'score' => round($score, 2),
                    'non_empty' => $nonEmpty,
                    'text_cells' => $textCells,
                    'keyword_hits' => $keywordHits,
                    'data_rows_below' => $dataRowsBelow,
                    'sample_values' => array_slice($values, 0, 10),
                ];
            }

            usort($headerCandidates, fn ($a, $b) => $b['score'] <=> $a['score']);
            $bestHeader = $headerCandidates[0] ?? null;

            // Puntuación de hoja: mejor cabecera + nº de filas de datos
            $sheetScore = 0.0;
            if ($bestHeader !== null) {
                $sheetScore = $bestHeader['score'] + ($nonEmptyRows * 0.1);
            }

            $sheetSummaries[] = [
                'index' => $index,
                'name' => $name,
                'total_rows' => $highestRow,
                'total_columns' => $highestColumnIndex,
                'non_empty_rows_scanned' => $nonEmptyRows,
                'header_row_candidates' => $headerCandidates,
                'best_header_row' => $bestHeader,
                'sheet_score' => round($sheetScore, 2),
            ];

            if ($sheetScore > $bestSheetScore) {
                $bestSheetScore = $sheetScore;
                $bestSheetIndex = $index;
            }
        }

        $selectedSheet = $bestSheetIndex !== null ? $spreadsheet->getSheet($bestSheetIndex) : null;
        $selectedHeaderRow = null;
        $columns = [];
        $rows = [];

        if ($selectedSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet) {
            $sheetInfo = collect($sheetSummaries)->firstWhere('index', $bestSheetIndex);
            $bestHeader = $sheetInfo['best_header_row'] ?? null;
            $selectedHeaderRow = $bestHeader['row'] ?? 1;

            $highestRow = $selectedSheet->getHighestRow();
            $highestColumn = $selectedSheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            // Construir cabecera a partir de la fila seleccionada
            $headers = [];
            $seen = [];
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                $coord = Coordinate::stringFromColumnIndex($c) . $selectedHeaderRow;
                $val = $selectedSheet->getCell($coord)->getValue();
                $name = $val !== null ? trim((string) $val) : ('col_' . Coordinate::stringFromColumnIndex($c));
                if ($name === '') {
                    $name = 'col_' . Coordinate::stringFromColumnIndex($c);
                }
                if (isset($seen[$name])) {
                    $seen[$name]++;
                    $name = $name . '_' . $seen[$name];
                } else {
                    $seen[$name] = 1;
                }
                $headers[] = $name;
            }
            $columns = $headers;

            // Leer filas de datos a partir de la fila siguiente
            $startRow = $selectedHeaderRow + 1;
            $maxRow = $limit !== null ? min($highestRow, $startRow - 1 + $limit) : $highestRow;
            for ($r = $startRow; $r <= $maxRow; $r++) {
                $assoc = [];
                $rowHasData = false;
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c) . $r;
                    $cell = $selectedSheet->getCell($coord);
                    $key = $headers[$c - 1] ?? ('col_' . Coordinate::stringFromColumnIndex($c));
                    $val = $this->getCellValueAsString($cell, $key);
                    if ($val !== '') {
                        $rowHasData = true;
                    }
                    $assoc[$key] = $val;
                }
                if ($rowHasData) {
                    $rows[] = $assoc;
                }
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'sheets' => $sheetSummaries,
            'selected_sheet_index' => $bestSheetIndex,
            'selected_sheet_name' => $selectedSheet?->getTitle(),
            'selected_header_row' => $selectedHeaderRow,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    /**
     * Lee el valor de una celda como string sin convertir códigos numéricos (EAN, UPC,
     * referencias, número de modelo) a fechas. Nunca interpretamos números como fechas Excel.
     */
    protected function getCellValueAsString(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, string $headerName = ''): string
    {
        $val = $cell->getValue();
        if ($val === null) {
            return '';
        }
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d H:i:s');
        }

        $header = $this->normalizeHeader($headerName);
        $isCodeColumn = $header !== '' && $this->headerMatchesAny($header, $this->codeHeaderHints);
        $isPriceColumn = $header !== '' && $this->headerMatchesAny($header, $this->priceHeaderHints);

        // Para columnas de códigos: usar el valor FORMATEADO/visible de Excel (preserva ceros a la izquierda y evita notación científica).
        // No convertimos números a fecha; si Excel ya formatea como fecha, devolverá la fecha visible (no podemos recuperar ceros perdidos si Excel ya los eliminó).
        if ($isCodeColumn) {
            $formatted = trim((string) $cell->getFormattedValue());
            if ($formatted !== '') {
                return $formatted;
            }
            // Fallback si no hay formattedValue (raro): tratar como raw.
        }

        // Para importes: preferimos valor crudo (sin símbolos, miles, etc.) para no romper el parseo posterior.
        if ($isPriceColumn) {
            if (is_numeric($val)) {
                // Mantener como string numérico simple.
                return (string) $val;
            }
            return trim((string) $val);
        }

        if (is_numeric($val)) {
            // Para el resto, el valor formateado suele ser más estable que el cast directo (evita notación científica).
            $formatted = trim((string) $cell->getFormattedValue());
            if ($formatted !== '') {
                return $formatted;
            }

            $f = (float) $val;
            if ($f === floor($f) && abs($f) <= PHP_INT_MAX) {
                return (string) (int) $f;
            }
            return (string) $val;
        }
        return trim((string) $val);
    }

    protected function normalizeHeader(string $header): string
    {
        $h = mb_strtolower(trim($header), 'UTF-8');
        $h = $this->removeAccents($h);
        // normalizar signos comunes
        $h = str_replace(['º', 'ª'], ['o', 'a'], $h);
        // dejar solo letras/números/espacios
        $h = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $h);
        $h = preg_replace('/\s+/u', ' ', $h);
        return trim($h ?? '');
    }

    /**
     * @param array<int,string> $needles
     */
    protected function headerMatchesAny(string $header, array $needles): bool
    {
        $tokens = $this->headerTokens($header);
        foreach ($needles as $n) {
            $n = $this->normalizeHeader($n);
            if ($n === '') {
                continue;
            }

            // Si el needle tiene espacios, exigimos match por substring ya normalizado (frase).
            if (str_contains($n, ' ')) {
                if (str_contains($header, $n)) {
                    return true;
                }
                continue;
            }

            // Si es una palabra suelta (p. ej. "ref"), hacemos match por token completo para evitar falsos positivos.
            if (isset($tokens[$n])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<string, true>
     */
    protected function headerTokens(string $normalizedHeader): array
    {
        if ($normalizedHeader === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $normalizedHeader, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];
        foreach ($parts as $p) {
            $tokens[$p] = true;
        }
        return $tokens;
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

    protected function ensureFileExists(string $path): void
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new \RuntimeException("El archivo no existe o no se puede leer: " . basename($path));
        }
    }
}
