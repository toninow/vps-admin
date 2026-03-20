<?php

namespace App\Services\Suppliers\Profiles;

use App\Models\Supplier;
use App\Models\SupplierImport;
use App\Services\Import\ImportTransformerService;

abstract class BaseSupplierProfile
{
    /**
     * Código lógico del proveedor (ej. 'adagio', 'fender', 'yamaha', 'knobloch').
     */
    abstract public function getLogicalCode(): string;

    /**
     * Nivel de madurez del perfil:
     * - 'specific'
     * - 'semi_specific'
     * - 'generic'
     */
    abstract public function getMaturityLevel(): string;

    /**
     * Variantes / patrones de archivo asociados a este proveedor lógico.
     *
     * @return string[]
     */
    public function getFilePatterns(): array
    {
        return [];
    }

    /**
     * Alias de cabecera por campo interno. Claves = ImportTransformerService::TARGET_FIELDS.
     *
     * @return array<string, string[]>
     */
    public function getHeaderAliases(): array
    {
        return [];
    }

    /**
     * Campos internos objetivo (única fuente de verdad: ImportTransformerService::TARGET_FIELDS).
     *
     * @return string[]
     */
    public function getTargetFields(): array
    {
        return ImportTransformerService::TARGET_FIELDS;
    }

    /**
     * Umbral mínimo de puntuación total (header + contenido) para aceptar un mapeo.
     * Por defecto: campos sensibles (brand, description, cost_price) exigen evidencia de cabecera;
     * el resto permite heurística de contenido con umbral bajo.
     * Prioridad: precisión sobre cobertura falsa.
     *
     * @return array<string, float>
     */
    protected function getMinimumScoreByTarget(): array
    {
        return [
            'name' => 2.0,
            'summary' => 2.0,
            'description' => 4.0,
            'ean13' => 2.0,
            'quantity' => 2.0,
            'price_tax_incl' => 2.0,
            'cost_price' => 5.0,
            'brand' => 5.0,
            'category_path_export' => 2.0,
            'tags' => 2.0,
            'supplier_reference' => 2.0,
            'image_urls' => 3.0,
        ];
    }

    /**
     * Sugerencia de mapping target_field => source_column para un import concreto.
     * Aplica umbral mínimo por target y reglas de exclusión para evitar falsos positivos
     * (p. ej. no usar name como brand/description ni price_tax_incl como cost_price sin evidencia de cabecera).
     *
     * @param  string[]  $columns
     * @param  array<int,array<string,string>>  $sampleRows
     * @return array<string,string>
     */
    public function suggestMapping(Supplier $supplier, SupplierImport $import, array $columns, array $sampleRows): array
    {
        $targetFields = $this->getTargetFields();
        $headerAliases = $this->getHeaderAliases();
        $minimumScores = $this->getMinimumScoreByTarget();

        $nameScoreByTargetColumn = [];
        $totalScoreByTargetColumn = [];

        foreach ($targetFields as $target) {
            $aliases = $headerAliases[$target] ?? [];
            foreach ($columns as $col) {
                $nameScore = $this->scoreColumnByHeaderName($target, $col, $aliases);
                $nameScoreByTargetColumn[$target][$col] = $nameScore;

                $contentScore = $this->scoreColumnByContent($target, $col, $sampleRows);
                $total = $nameScore + $contentScore;
                if ($total > 0) {
                    $totalScoreByTargetColumn[$target][$col] = $total;
                }
            }
        }

        $result = [];
        $minScore = fn (string $t) => $minimumScores[$t] ?? 2.0;

        foreach ($targetFields as $target) {
            $scores = $totalScoreByTargetColumn[$target] ?? [];
            if ($scores === []) {
                continue;
            }
            arsort($scores);
            $bestColumn = array_key_first($scores);
            $bestScore = $scores[$bestColumn];

            if ($bestScore < $minScore($target)) {
                continue;
            }

            $nameScoreForBest = $nameScoreByTargetColumn[$target][$bestColumn] ?? 0;

            if ($target === 'brand') {
                $nameColumn = $result['name'] ?? null;
                if ($nameColumn !== null && $bestColumn === $nameColumn && $nameScoreForBest < 2.0) {
                    continue;
                }
            }

            if ($target === 'description') {
                $nameColumn = $result['name'] ?? null;
                if ($nameColumn !== null && $bestColumn === $nameColumn && $nameScoreForBest < 2.0) {
                    continue;
                }
            }

            if ($target === 'cost_price') {
                $priceColumn = $result['price_tax_incl'] ?? null;
                if ($priceColumn !== null && $bestColumn === $priceColumn && $nameScoreForBest < 2.0) {
                    continue;
                }
            }

            $result[$target] = $bestColumn;
        }

        unset($result['quantity']);

        return $result;
    }

    /**
     * Normaliza una cabecera para matching: minúsculas, sin acentos, sin símbolos raros, espacios colapsados.
     * Permite emparejar "N.º UPC" con "numero upc", "Número de artículo" con "numero de articulo", etc.
     */
    protected function normalizeHeaderForMatching(string $header): string
    {
        $s = mb_strtolower($header, 'UTF-8');
        $s = $this->removeAccents($s);
        $s = str_replace(['º', 'ª'], ['o', 'a'], $s);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    /**
     * @param  string[]  $aliases
     */
    protected function scoreColumnByHeaderName(string $target, string $columnName, array $aliases): float
    {
        $score = 0.0;
        $normCol = $this->normalizeHeaderForMatching($columnName);
        if ($normCol === '') {
            return 0.0;
        }

        foreach ($aliases as $alias) {
            $normAlias = $this->normalizeHeaderForMatching($alias);
            if ($normAlias === '') {
                continue;
            }
            if ($normAlias === $normCol) {
                $score += 5.0;
            } elseif (str_contains($normCol, $normAlias) || str_contains($normAlias, $normCol)) {
                $score += 2.0;
            }
        }

        return $score;
    }

    /**
     * Puntuación heurística por contenido. Se puede sobreescribir por proveedor.
     *
     * @param  array<int,array<string,string>>  $sampleRows
     */
    protected function scoreColumnByContent(string $target, string $columnName, array $sampleRows): float
    {
        $values = [];
        foreach ($sampleRows as $row) {
            if (array_key_exists($columnName, $row)) {
                $v = trim((string) $row[$columnName]);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
            if (count($values) >= 200) {
                break;
            }
        }
        if ($values === []) {
            return 0.0;
        }

        $score = 0.0;

        if ($target === 'ean13') {
            $numericCount = 0;
            $len13 = 0;
            $len12 = 0;
            $len8 = 0;
            foreach ($values as $v) {
                $digits = preg_replace('/\D/', '', $v);
                if ($digits === '') {
                    continue;
                }
                if ($digits === $v) {
                    $numericCount++;
                }
                $len = strlen($digits);
                if ($len === 13) {
                    $len13++;
                } elseif ($len === 12) {
                    $len12++;
                } elseif ($len === 8) {
                    $len8++;
                }
            }
            $total = count($values);
            if ($total > 0) {
                $score += ($numericCount / $total) * 2.0;
                $score += ($len13 / $total) * 4.0;
                $score += ($len12 / $total) * 2.0;
                $score += ($len8 / $total) * 1.5;
            }
        } elseif (in_array($target, ['name', 'summary', 'description'], true)) {
            $avgLen = array_sum(array_map('mb_strlen', $values)) / max(count($values), 1);
            if ($avgLen >= 5) {
                $score += 1.0;
            }
        } elseif ($target === 'brand') {
            $shortCount = 0;
            foreach ($values as $v) {
                if (mb_strlen($v) <= 20 && str_word_count($v) <= 3) {
                    $shortCount++;
                }
            }
            $score += ($shortCount / count($values)) * 2.0;
        } elseif ($target === 'quantity') {
            $numericRows = 0;
            foreach ($values as $v) {
                if (is_numeric(str_replace(',', '.', $v))) {
                    $numericRows++;
                }
            }
            $score += ($numericRows / count($values)) * 2.0;
        } elseif (in_array($target, ['price_tax_incl', 'cost_price'], true)) {
            $decimalRows = 0;
            foreach ($values as $v) {
                $vv = str_replace(',', '.', $v);
                if (is_numeric($vv) && strpos($vv, '.') !== false) {
                    $decimalRows++;
                }
            }
            $score += ($decimalRows / count($values)) * 2.0;
        } elseif ($target === 'image_urls') {
            $urlLike = 0;
            foreach ($values as $v) {
                if (preg_match('/https?:\/\/|\.jpg|\.jpeg|\.png|\.gif/i', $v)) {
                    $urlLike++;
                }
            }
            $score += ($urlLike / count($values)) * 3.0;
        }

        return $score;
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
