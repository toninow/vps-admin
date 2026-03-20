<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Models\SupplierImport;
use App\Models\User;
use App\Services\Import\FileReaderFactory;
use App\Services\Import\FileTypeDetector;
use App\Services\Import\ImportFromFileService;
use App\Services\Import\ImportTransformerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateTestImportCommand extends Command
{
    protected $signature = 'imports:create-test
                            {--supplier= : Nombre o parte del nombre del proveedor (ADAGIO, GEWA, FENDER, YAMAHA)}
                            {--file= : Ruta al archivo CSV/Excel/XML (absoluta o relativa al proyecto)}
                            {--only-create : Solo crear registro y copiar archivo; no mapear ni procesar}
                            {--only-preview : Mostrar columnas y primeras filas; no guardar mapeo}
                            {--only-map : Guardar mapeo y filas pero no ejecutar transformación}
                            {--show-suggested : Mostrar tabla del mapeo sugerido antes de aplicar}
                            {--force : Sobrescribir archivo en storage si ya existe}';

    protected $description = 'Carga una importación de prueba desde un archivo real en el servidor (mapeo sugerido por perfil, filas, transformación).';

    public function handle(
        FileTypeDetector $detector,
        FileReaderFactory $readerFactory,
        ImportFromFileService $importFromFileService,
        ImportTransformerService $transformer
    ): int {
        $supplierName = $this->option('supplier');
        $filePath = $this->option('file');

        if (! $supplierName || ! $filePath) {
            $this->error('Indica --supplier=ADAGIO y --file=storage/app/adagio.csv (o ruta absoluta).');
            return self::FAILURE;
        }

        $resolvedPath = $filePath;
        if (! str_starts_with($filePath, '/')) {
            $resolvedPath = base_path($filePath);
        }
        $resolvedPath = realpath($resolvedPath) ?: $resolvedPath;
        if (! is_file($resolvedPath) || ! is_readable($resolvedPath)) {
            $this->error('El archivo no existe o no es legible.');
            $this->line('Ruta probada: ' . $resolvedPath);
            $this->line('Copia el CSV/Excel a esa ruta (ej. storage/app/adagio.csv) o usa una ruta absoluta donde ya exista el archivo.');
            return self::FAILURE;
        }
        $filePath = $resolvedPath;

        $term = '%' . $supplierName . '%';
        $supplier = Supplier::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('code', 'like', $term);
            })
            ->first();

        if (! $supplier) {
            $this->error('No se encontró ningún proveedor activo con nombre/código/slug que contenga: ' . $supplierName);
            $active = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'slug']);
            if ($active->isNotEmpty()) {
                $this->line('');
                $this->info('Proveedores activos disponibles (usa --supplier= con nombre, code o slug):');
                foreach ($active as $s) {
                    $this->line(sprintf('  id=%d  name=%s  code=%s  slug=%s', $s->id, $s->name, $s->code ?? '-', $s->slug));
                }
            } else {
                $this->line('No hay proveedores activos. Crea uno desde la aplicación (Proveedores) o con un seeder.');
            }
            return self::FAILURE;
        }

        $fileType = $detector->detectFromPath($filePath);
        $allowed = [FileTypeDetector::TYPE_CSV, FileTypeDetector::TYPE_XLSX, FileTypeDetector::TYPE_XML];
        if (! in_array($fileType, $allowed, true)) {
            $this->error('Tipo de archivo no soportado. Use CSV, XLSX o XML.');
            return self::FAILURE;
        }

        $filenameOriginal = basename($filePath);
        $ext = pathinfo($filenameOriginal, PATHINFO_EXTENSION) ?: $fileType;

        $userId = User::query()->value('id');

        $import = SupplierImport::create([
            'supplier_id' => $supplier->id,
            'user_id' => $userId,
            'filename_original' => $filenameOriginal,
            'file_path' => '',
            'file_type' => $fileType,
            'catalog_year' => now()->year,
            'status' => 'uploaded',
        ]);

        $dir = "supplier-imports/{$import->id}";
        $storagePath = $dir . '/file.' . $ext;
        $fullStoragePath = Storage::disk('local')->path($storagePath);
        if (! is_dir(dirname($fullStoragePath))) {
            mkdir(dirname($fullStoragePath), 0755, true);
        }
        copy($filePath, $fullStoragePath);

        $import->update(['file_path' => $storagePath]);
        $detectedFromContent = $detector->detectFromPath($fullStoragePath);
        if (in_array($detectedFromContent, $allowed, true)) {
            $import->update(['file_type' => $detectedFromContent]);
        }

        $this->info('Importación creada: #' . $import->id . ' | ' . $supplier->name . ' | ' . $filenameOriginal);
        $this->line('Tipo detectado: ' . $import->file_type);
        $this->newLine();

        if ($this->option('only-create')) {
            $this->outputSummary($import, null, null, null, true);
            return self::SUCCESS;
        }

        $import->load('supplier');
        $pathForRead = Storage::disk('local')->path($import->file_path);
        $reader = $readerFactory->getReaderForType($import->file_type);
        $columns = $reader->getColumnNames($pathForRead);
        $sampleRows = $reader->readRows($pathForRead, 200);
        $previewRows = $reader->readRows($pathForRead, 15);

        if (empty($columns) && empty($sampleRows)) {
            $this->error('El archivo está vacío o no se detectaron columnas.');
            return self::FAILURE;
        }

        $this->line('Columnas detectadas: ' . count($columns));
        $this->line(implode(', ', array_slice($columns, 0, 20)) . (count($columns) > 20 ? '...' : ''));
        $this->newLine();

        if ($this->option('only-preview')) {
            $tableRows = [];
            foreach (array_slice($previewRows, 0, 10) as $row) {
                $tableRows[] = array_map(fn ($col) => $row[$col] ?? '', $columns);
            }
            $this->table($columns, $tableRows);
            $this->outputSummary($import, count($previewRows), null, $columns, true);
            return self::SUCCESS;
        }

        $columnsMap = $importFromFileService->buildSuggestedColumnsMap($import, $columns, $sampleRows);

        if ($this->option('show-suggested')) {
            $this->info('=== Mapeo sugerido (perfil + aliases) ===');
            $rows = [];
            foreach ($columnsMap as $target => $source) {
                $rows[] = [$target, $source];
            }
            $this->table(['Target', 'Columna origen'], $rows);
            $this->newLine();
        }

        $importFromFileService->persistMappingAndRows($import, $columnsMap, $pathForRead, $userId);
        $totalRows = $import->fresh()->total_rows;

        $this->info('Mapeo guardado. Filas persistidas: ' . $totalRows);

        if ($this->option('only-map')) {
            $this->outputSummary($import, $totalRows, null, $columns, true, $columnsMap);
            return self::SUCCESS;
        }

        $result = $transformer->transformImportToNormalizedProducts($import->fresh());

        $this->outputSummary($import->fresh(), $totalRows, $result, $columns, false, $columnsMap);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>|null  $columns
     * @param  array<string, string>|null  $columnsMap
     */
    private function outputSummary(SupplierImport $import, ?int $rowsRead, ?array $processResult, ?array $columns, bool $partial, ?array $columnsMap = null): void
    {
        $this->newLine();
        $this->line('--- Resumen ---');
        $this->line('import_id: ' . $import->id);
        $this->line('proveedor: ' . ($import->supplier->name ?? '—'));
        $this->line('archivo: ' . $import->filename_original);
        $this->line('tipo: ' . $import->file_type);
        if ($columns !== null) {
            $this->line('columnas detectadas: ' . count($columns));
        }
        if ($columnsMap !== null) {
            $this->line('mapping aplicado: ' . count($columnsMap) . ' campos');
        }
        if ($rowsRead !== null) {
            $this->line('filas leídas: ' . $rowsRead);
        }
        if ($processResult !== null) {
            $this->line('filas procesadas (OK): ' . ($processResult['created'] ?? 0));
            $this->line('errores: ' . ($processResult['errors'] ?? 0));
            $this->line('omitidas: ' . ($processResult['skipped'] ?? 0));
        }
        if ($partial) {
            $this->line('(flujo parcial; usa sin --only-* para crear + mapear + procesar)');
        }
    }
}
