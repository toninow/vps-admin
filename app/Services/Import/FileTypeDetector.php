<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;

class FileTypeDetector
{
    public const TYPE_CSV = 'csv';

    public const TYPE_XLSX = 'xlsx';

    public const TYPE_XML = 'xml';

    /**
     * Extensiones permitidas para subida.
     *
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return ['csv', 'txt', 'xlsx', 'xls', 'xml'];
    }

    /**
     * MIME types permitidos (referencia; no depender solo de ellos).
     *
     * @return array<int, string>
     */
    public function allowedMimes(): array
    {
        return [
            'text/csv',
            'text/plain',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/xml',
            'application/xml',
        ];
    }

    /**
     * Comprueba si la extensión está permitida.
     */
    public function isExtensionAllowed(string $extension): bool
    {
        return in_array(strtolower($extension), $this->allowedExtensions(), true);
    }

    /**
     * Detecta el tipo por extensión del cliente (navegador). No fiable solo.
     */
    public function detectFromExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = strtolower($file->getMimeType() ?: '');

        if ($extension === 'xml' || $mime === 'text/xml' || $mime === 'application/xml') {
            return self::TYPE_XML;
        }
        if (in_array($extension, ['xlsx', 'xls'], true) || str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel')) {
            return self::TYPE_XLSX;
        }
        if (in_array($extension, ['csv', 'txt'], true) || $mime === 'text/csv' || $mime === 'text/plain') {
            return self::TYPE_CSV;
        }

        return self::TYPE_CSV;
    }

    /**
     * Detecta el tipo real leyendo el contenido del archivo (magic bytes).
     * Debe usarse tras guardar el archivo en disco.
     */
    public function detectFromPath(string $path): string
    {
        if (! is_file($path) || ! file_exists($path) || ! is_readable($path)) {
            return self::TYPE_CSV;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return self::TYPE_CSV;
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        if ($bytes === false || $bytes === '') {
            return self::TYPE_CSV;
        }

        if (str_starts_with($bytes, 'PK')) {
            return self::TYPE_XLSX;
        }
        if (str_starts_with(ltrim($bytes), '<?xml') || str_starts_with(ltrim($bytes), '<')) {
            return self::TYPE_XML;
        }

        return self::TYPE_CSV;
    }

    /**
     * Detecta tipo: primero por contenido real, luego por extensión/MIME si hay conflicto.
     */
    public function detect(UploadedFile $file): string
    {
        $byExtension = $this->detectFromExtension($file);
        $path = $file->getRealPath();
        if ($path && is_readable($path)) {
            $byContent = $this->detectFromPath($path);
            if ($byContent === self::TYPE_XML || $byContent === self::TYPE_XLSX) {
                return $byContent;
            }
        }
        return $byExtension;
    }
}
