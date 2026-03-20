<?php

namespace App\Services\Import;

use SimpleXMLElement;

class XmlFileReader implements FileReaderInterface
{
    /**
     * Rutas XPath para listas de filas (se prueba en orden).
     *
     * @var array<int, string>
     */
    protected array $rowPaths = ['//row', '//item', '//product', '//producto', '//record', '//*[local-name()="row"]', '/*/*', '/*'];

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
        $this->ensureFileExists($path);
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($content);
        } catch (\Throwable $e) {
            libxml_clear_errors();
            throw new \RuntimeException('XML no válido: ' . $e->getMessage());
        }
        libxml_clear_errors();

        $rows = [];
        foreach ($this->rowPaths as $xpath) {
            $nodes = @$xml->xpath($xpath);
            if ($nodes === false || empty($nodes)) {
                continue;
            }
            foreach ($nodes as $node) {
                $flat = $this->flattenNode($node);
                if ($flat === []) {
                    continue;
                }
                $rows[] = $flat;
                if ($limit !== null && count($rows) >= $limit) {
                    break 2;
                }
            }
            if (! empty($rows)) {
                break;
            }
        }

        if (empty($rows)) {
            $flat = $this->flattenNode($xml);
            if ($flat !== []) {
                $rows[] = $flat;
            }
        }

        return $rows;
    }

    protected function ensureFileExists(string $path): void
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new \RuntimeException("El archivo no existe o no se puede leer: " . basename($path));
        }
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
