<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

class CsvFileReader implements FileReaderInterface
{
    /**
     * Delimitadores a probar (orden de preferencia).
     *
     * @var array<int, string>
     */
    protected array $delimiters = [',', ';', "\t", '|'];

    /**
     * Encodings a probar para lectura (el archivo se normaliza a UTF-8).
     *
     * @var array<int, string>
     */
    protected array $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];

    public function getColumnNames(string $path): array
    {
        $this->ensureFileExists($path);
        $structure = $this->detectStructure($path);
        $handle = $this->open($path);
        $first = $this->readCsvRecord($handle, $structure['header_delimiter']);
        fclose($handle);

        if ($first === false || empty($first)) {
            return [];
        }

        $first = $this->normalizeEncoding($first);
        return $this->normalizeKeys($first);
    }

    public function readRows(string $path, ?int $limit = null): array
    {
        $this->ensureFileExists($path);
        $structure = $this->detectStructure($path);
        $handle = $this->open($path);
        $rows = [];
        $headers = null;
        $isHeader = true;

        while (($line = $this->readCsvRecord($handle, $isHeader ? $structure['header_delimiter'] : $structure['row_delimiter'])) !== false) {
            $line = $this->normalizeEncoding($line);
            if ($headers === null) {
                $headers = $this->normalizeKeys($line);
                $isHeader = false;
                if (empty($headers)) {
                    continue;
                }
                continue;
            }

            $headerCount = count($headers);
            // Aseguramos que la línea tenga exactamente el mismo número de columnas que los headers
            $values = array_slice($line, 0, $headerCount);
            if (count($values) < $headerCount) {
                $values = array_pad($values, $headerCount, null);
            }

            $assoc = array_combine($headers, $values);
            if ($assoc !== false) {
                $rows[] = array_map(fn ($v) => $v !== null ? trim((string) $v) : '', $assoc);
            }
            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Devuelve metadatos simples del archivo CSV (delimitador y encoding detectado).
     *
     * @return array{delimiter:string,encoding:string|null}
     */
    public function getFileMetadata(string $path): array
    {
        $this->ensureFileExists($path);

        $structure = $this->detectStructure($path);

        $handle = $this->open($path);
        $firstLine = fgets($handle);
        fclose($handle);

        $encoding = null;
        if ($firstLine !== false && $firstLine !== '') {
            $encoding = $this->detectEncodingForString($firstLine);
        }

        return [
            'delimiter' => $structure['row_delimiter'],
            'encoding' => $encoding,
        ];
    }

    protected function ensureFileExists(string $path): void
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new \RuntimeException("El archivo no existe o no se puede leer: {$path}");
        }
    }

    protected function open(string $path)
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("No se pudo abrir el archivo CSV: " . basename($path));
        }
        return $handle;
    }

    /**
     * Detecta delimitador de cabecera y delimitador de filas.
     *
     * Algunos proveedores mezclan ambos, por ejemplo cabecera con `,` y datos con `;`.
     *
     * @return array{header_delimiter:string,row_delimiter:string}
     */
    protected function detectStructure(string $path): array
    {
        $handle = $this->open($path);
        $lines = [];

        while (($line = fgets($handle)) !== false) {
            if (trim($line) === '') {
                continue;
            }
            $lines[] = $line;
            if (count($lines) >= 12) {
                break;
            }
        }
        fclose($handle);

        if ($lines === []) {
            return [
                'header_delimiter' => ',',
                'row_delimiter' => ',',
            ];
        }

        $normalizedLines = $this->normalizeEncoding($lines);
        $headerLine = $normalizedLines[0];

        $headerDelimiter = $this->detectDelimiterFromLines([$headerLine], ',');
        $rowDelimiter = $this->detectRowDelimiterByRecords($path, $headerDelimiter);

        return [
            'header_delimiter' => $headerDelimiter,
            'row_delimiter' => $rowDelimiter,
        ];
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function detectDelimiterFromLines(array $lines, string $fallback = ','): string
    {
        $best = $fallback;
        $bestScore = -1;

        foreach ($this->delimiters as $d) {
            $score = 0;
            foreach ($lines as $line) {
                $parsed = str_getcsv($line, $d, '"', '');
                $columns = count($parsed);
                if ($columns > 1) {
                    $score += $columns;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $d;
            }
        }

        return $best;
    }

    /**
     * Detecta el delimitador real de los registros usando fgetcsv sobre registros completos.
     *
     * Evita falsos positivos cuando el CSV contiene HTML multilínea dentro de campos entrecomillados.
     */
    protected function detectRowDelimiterByRecords(string $path, string $headerDelimiter): string
    {
        $headerHandle = $this->open($path);
        $headers = $this->readCsvRecord($headerHandle, $headerDelimiter);
        fclose($headerHandle);

        if ($headers === false || $headers === []) {
            return $headerDelimiter;
        }

        $headerCount = count($headers);
        $bestDelimiter = $headerDelimiter;
        $bestScore = PHP_INT_MIN;

        foreach ($this->delimiters as $delimiter) {
            $handle = $this->open($path);
            $this->readCsvRecord($handle, $headerDelimiter);

            $score = 0;
            $records = 0;

            while (($row = $this->readCsvRecord($handle, $delimiter)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $records++;
                $columnCount = count($row);

                if ($columnCount === $headerCount) {
                    $score += 8;
                } elseif ($columnCount > 1) {
                    $score += max(1, 4 - abs($columnCount - $headerCount));
                } else {
                    $score -= 3;
                }

                if ($records >= 25) {
                    break;
                }
            }

            fclose($handle);

            if ($records === 0) {
                continue;
            }

            $score += min($records, 25);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    /**
     * Lee un registro CSV sin usar el backslash escape heredado de PHP.
     *
     * Algunos proveedores meten HTML con secuencias como \" dentro de campos entrecomillados,
     * y el escape por defecto rompe la lectura del registro.
     */
    protected function readCsvRecord($handle, string $delimiter): array|false
    {
        return fgetcsv($handle, 0, $delimiter, '"', '');
    }

    /**
     * Detecta el encoding de una cadena de texto usando la lista de encodings configurada.
     */
    protected function detectEncodingForString(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return 'UTF-8';
        }

        foreach ($this->encodings as $enc) {
            if ($enc === 'UTF-8') {
                continue;
            }
            $converted = @mb_convert_encoding($value, 'UTF-8', $enc);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $enc;
            }
        }

        return null;
    }

    /**
     * Intenta convertir a UTF-8 si la cadena no es válida UTF-8.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function normalizeEncoding(array $values): array
    {
        return array_map(function ($v) {
            $v = (string) $v;
            if ($v === '') {
                return $v;
            }

            $encoding = $this->detectEncodingForString($v);
            if ($encoding !== null && $encoding !== 'UTF-8') {
                $converted = @mb_convert_encoding($v, 'UTF-8', $encoding);
                if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                    return $converted;
                }
            }

            return $v;
        }, $values);
    }

    /**
     * Normaliza nombres de columna: trim, quita BOM, claves únicas.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    protected function normalizeKeys(array $keys): array
    {
        $seen = [];
        $out = [];
        foreach ($keys as $i => $k) {
            $k = trim((string) $k);
            $k = Str::remove("\xEF\xBB\xBF", $k);
            if ($k === '') {
                $k = 'col_' . ($i + 1);
            }
            if (isset($seen[$k])) {
                $seen[$k]++;
                $k = $k . '_' . $seen[$k];
            } else {
                $seen[$k] = 1;
            }
            $out[] = $k;
        }
        return $out;
    }
}
