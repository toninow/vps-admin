<?php

namespace App\Services\Import;

use SimpleXMLElement;
use XMLReader;

class XmlFileReader implements FileReaderInterface
{
    /**
     * Nombres de nodo preferidos para detectar filas de catálogo.
     *
     * @var array<int, string>
     */
    protected array $preferredRowNames = ['row', 'item', 'product', 'producto', 'record'];

    public function getColumnNames(string $path): array
    {
        $rows = $this->readRows($path, 1);
        if (empty($rows)) {
            return [];
        }
        return array_keys($rows[0]);
    }

    public function readRows(string $path, ?int $limit = null): array
    {
        $rows = [];
        $this->streamRows($path, function (array $row) use (&$rows) {
            $rows[] = $row;
        }, $limit);

        return $rows;
    }

    /**
     * Recorre el XML en streaming y entrega cada fila al callback sin cargar el archivo entero en memoria.
     *
     * @param  callable(array<string, string>, int):void  $callback
     */
    public function streamRows(string $path, callable $callback, ?int $limit = null): int
    {
        $this->ensureFileExists($path);

        $rowPattern = $this->detectRowPattern($path);
        $reader = $this->openReader($path);
        $count = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            if (! $this->matchesRowPattern($reader, $rowPattern)) {
                continue;
            }

            $outerXml = $reader->readOuterXML();
            if (! is_string($outerXml) || trim($outerXml) === '') {
                continue;
            }

            $flat = $this->parseRowFragment($outerXml);
            if ($flat === []) {
                continue;
            }

            $count++;
            $callback($flat, $count);

            if ($limit !== null && $count >= $limit) {
                break;
            }
        }

        $reader->close();

        return $count;
    }

    protected function ensureFileExists(string $path): void
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new \RuntimeException("El archivo no existe o no se puede leer: " . basename($path));
        }
    }

    /**
     * @return array{name:string, depth:int}
     */
    protected function detectRowPattern(string $path): array
    {
        $reader = $this->openReader($path);

        $rootDepth = null;
        $rootElement = null;
        $shallowCounts = [];
        $fallback = null;
        $scanned = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $scanned++;
            $depth = $reader->depth;
            $name = $reader->name;
            $localName = strtolower($reader->localName ?: $name);

            if ($rootDepth === null) {
                $rootDepth = $depth;
                $rootElement = $name;
                continue;
            }

            if (in_array($localName, $this->preferredRowNames, true)) {
                $reader->close();

                return [
                    'name' => $name,
                    'depth' => $depth,
                ];
            }

            if ($fallback === null && $depth === $rootDepth + 1 && $name !== $rootElement) {
                $fallback = [
                    'name' => $name,
                    'depth' => $depth,
                ];
            }

            if ($depth === $rootDepth + 1) {
                $key = $depth . '|' . $name;
                $shallowCounts[$key] = ($shallowCounts[$key] ?? 0) + 1;
            }

            if ($scanned >= 4000) {
                break;
            }
        }

        $reader->close();

        if ($shallowCounts !== []) {
            arsort($shallowCounts);
            $bestKey = array_key_first($shallowCounts);
            if (is_string($bestKey)) {
                [$depth, $name] = explode('|', $bestKey, 2);

                return [
                    'name' => $name,
                    'depth' => (int) $depth,
                ];
            }
        }

        return $fallback ?? [
            'name' => $rootElement ?? 'row',
            'depth' => ($rootDepth ?? 0) + 1,
        ];
    }

    /**
     * @param  array{name:string, depth:int}  $rowPattern
     */
    protected function matchesRowPattern(XMLReader $reader, array $rowPattern): bool
    {
        return $reader->depth === $rowPattern['depth']
            && $reader->name === $rowPattern['name'];
    }

    protected function openReader(string $path): XMLReader
    {
        $reader = new XMLReader();
        libxml_use_internal_errors(true);

        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            libxml_clear_errors();

            throw new \RuntimeException('No se pudo abrir el archivo XML.');
        }

        return $reader;
    }

    /**
     * @return array<string, string>
     */
    protected function parseRowFragment(string $outerXml): array
    {
        libxml_use_internal_errors(true);

        try {
            $node = new SimpleXMLElement($outerXml);
        } catch (\Throwable $e) {
            libxml_clear_errors();

            return [];
        }

        libxml_clear_errors();

        return $this->flattenNode($node);
    }

    /**
     * Convierte un nodo en array asociativo (hijos directos; nodos anidados como texto JSON o valor).
     *
     * @return array<string, string>
     */
    protected function flattenNode(SimpleXMLElement $node): array
    {
        $out = [];
        $duplicates = [];
        foreach ($node->children() as $name => $child) {
            $key = (string) $name;
            if ($key === '') {
                continue;
            }
            $childCount = $child->children()->count();
            if ($childCount > 0) {
                $val = trim((string) $child);
                if ($val === '') {
                    $nested = $this->flattenNode($child);
                    $val = $nested === [] ? '' : json_encode($nested, JSON_UNESCAPED_UNICODE);
                }
            } else {
                $val = trim((string) $child);
            }
            if (isset($out[$key])) {
                $duplicates[$key] = ($duplicates[$key] ?? 1) + 1;
                $key = $key . '_' . $duplicates[$key];
            }
            $out[$key] = $val;
        }
        if (empty($out)) {
            $text = trim((string) $node);
            if ($text !== '') {
                $out['value'] = $text;
            }
        }
        return $out;
    }
}
