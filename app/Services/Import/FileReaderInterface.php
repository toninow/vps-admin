<?php

namespace App\Services\Import;

interface FileReaderInterface
{
    /**
     * Lee filas del archivo. Cada fila es un array asociativo [ nombre_columna => valor ].
     *
     * @param  string  $path  Ruta absoluta al archivo
     * @param  int|null  $limit  Límite de filas (null = todas)
     * @return array<int, array<string, string>>
     */
    public function readRows(string $path, ?int $limit = null): array;

    /**
     * Devuelve los nombres de columnas detectados (primera fila o cabecera).
     *
     * @param  string  $path  Ruta absoluta al archivo
     * @return array<int, string>
     */
    public function getColumnNames(string $path): array;
}
