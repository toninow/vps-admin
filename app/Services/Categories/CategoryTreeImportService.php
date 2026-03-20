<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategoryTreeImportService
{
    public function importFromFile(string $path, bool $truncate = false, bool $dryRun = false): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('El archivo de categorías no existe o no es legible: ' . $path);
        }

        $paths = $this->loadPaths($path);

        if ($paths->isEmpty()) {
            return [
                'paths_total' => 0,
                'created' => 0,
                'reused' => 0,
                'categories_total' => Category::count(),
            ];
        }

        $created = 0;
        $reused = 0;

        $runner = function () use ($paths, $dryRun, &$created, &$reused): void {
            foreach ($paths as $segments) {
                $parentId = null;

                foreach ($segments as $index => $name) {
                    $slug = Str::slug($name);
                    if ($slug === '') {
                        $slug = 'category-' . Str::random(8);
                    }

                    $existing = Category::query()
                        ->where('parent_id', $parentId)
                        ->where(function ($query) use ($name, $slug) {
                            $query->where('name', $name)->orWhere('slug', $slug);
                        })
                        ->first();

                    if ($existing) {
                        $parentId = $existing->id;
                        $reused++;
                        continue;
                    }

                    if ($dryRun) {
                        $created++;
                        $parentId = -1 - $index;
                        continue;
                    }

                    $category = Category::create([
                        'parent_id' => $parentId,
                        'name' => $name,
                        'slug' => $slug,
                        'position' => 0,
                        'is_active' => true,
                    ]);

                    $created++;
                    $parentId = $category->id;
                }
            }
        };

        if ($truncate && ! $dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('categories')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return [
            'paths_total' => $paths->count(),
            'created' => $created,
            'reused' => $reused,
            'categories_total' => $dryRun ? Category::count() : Category::count(),
        ];
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    protected function loadPaths(string $path): Collection
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->loadJsonPaths($path),
            'csv', 'tsv' => $this->loadDelimitedPaths($path),
            'xlsx', 'xls' => $this->loadSpreadsheetPaths($path),
            default => $this->loadLinePaths($path),
        };
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    protected function loadJsonPaths(string $path): Collection
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('El JSON de categorías no es válido.');
        }

        $paths = collect();
        foreach ($decoded as $item) {
            foreach ($this->extractJsonPaths($item) as $segments) {
                $normalized = $this->normalizeSegments($segments);
                if ($normalized !== []) {
                    $paths->push($normalized);
                }
            }
        }

        return $paths->values();
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function extractJsonPaths(mixed $item, array $prefix = []): array
    {
        if (is_string($item)) {
            return [$this->splitPathString($item)];
        }

        if (is_array($item) && array_is_list($item)) {
            if ($item === []) {
                return [];
            }

            $allStrings = collect($item)->every(static fn ($value) => is_string($value));
            if ($allStrings) {
                return [$item];
            }

            $paths = [];
            foreach ($item as $child) {
                $paths = array_merge($paths, $this->extractJsonPaths($child, $prefix));
            }

            return $paths;
        }

        if (is_array($item)) {
            if (isset($item['path']) && is_string($item['path'])) {
                return [$this->splitPathString($item['path'])];
            }

            $name = trim((string) ($item['name'] ?? $item['label'] ?? ''));
            $nextPrefix = $name !== '' ? array_merge($prefix, [$name]) : $prefix;
            $children = $item['children'] ?? [];

            if (is_array($children) && $children !== []) {
                $paths = [];
                foreach ($children as $child) {
                    $paths = array_merge($paths, $this->extractJsonPaths($child, $nextPrefix));
                }

                return $paths;
            }

            return $nextPrefix === [] ? [] : [$nextPrefix];
        }

        return [];
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    protected function loadDelimitedPaths(string $path): Collection
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('No se pudo abrir el archivo CSV/TSV de categorías.');
        }

        $firstLine = '';
        while (($firstLine = fgets($handle)) !== false) {
            if (trim($firstLine) !== '') {
                break;
            }
        }
        rewind($handle);

        $delimiter = $this->detectDelimiter($firstLine);
        $rows = collect();

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows->push(array_map(static fn ($value) => trim((string) $value), $data));
        }

        fclose($handle);

        if ($rows->isEmpty()) {
            return collect();
        }

        $headers = $rows->first() ?? [];
        $headerMap = array_map(static fn ($header) => Str::lower((string) $header), $headers);
        $pathIndex = collect($headerMap)->search(static fn ($header) => in_array($header, ['path', 'ruta', 'category_path', 'full_path'], true));

        $dataRows = $pathIndex === false ? $rows : $rows->slice(1);

        return $dataRows
            ->map(function (array $row) use ($pathIndex) {
                if ($pathIndex !== false) {
                    return $this->splitPathString((string) ($row[$pathIndex] ?? ''));
                }

                return $this->normalizeSegments($row);
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    protected function loadLinePaths(string $path): Collection
    {
        return collect(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
            ->map(fn ($line) => $this->splitPathString((string) $line))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<int, string>>
     */
    protected function loadSpreadsheetPaths(string $path): Collection
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);
        $maxRow = $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $rows = collect();

        for ($row = 1; $row <= $maxRow; $row++) {
            $cells = [];
            for ($col = 1; $col <= $maxCol; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col) . $row;
                $cells[] = trim((string) $sheet->getCell($coord)->getFormattedValue());
            }
            $rows->push($cells);
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        $firstRow = $rows->first() ?? [];
        $isHeader = collect($firstRow)->filter()->every(static function (string $value): bool {
            $value = Str::lower($value);
            return str_contains($value, 'categoria') || str_contains($value, 'subcategoria');
        });

        return ($isHeader ? $rows->slice(1) : $rows)
            ->map(fn (array $row) => $this->normalizeSegments($row))
            ->filter()
            ->values();
    }

    /**
     * @return array<int, string>
     */
    protected function splitPathString(string $path): array
    {
        $normalized = str_replace(['›', '»', '→', '\\'], '>', $path);

        return $this->normalizeSegments(preg_split('/\s*(?:>|\/|\|)\s*/u', $normalized) ?: []);
    }

    /**
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    protected function normalizeSegments(array $segments): array
    {
        $clean = [];

        foreach ($segments as $segment) {
            $value = trim(preg_replace('/\s+/u', ' ', (string) $segment) ?? '');
            if ($value === '') {
                continue;
            }
            $clean[] = $value;
        }

        return $clean;
    }

    protected function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $score = substr_count($line, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }
}
