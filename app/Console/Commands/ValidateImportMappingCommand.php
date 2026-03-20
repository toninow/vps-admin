<?php

namespace App\Console\Commands;

use App\Models\SupplierImport;
use App\Services\Import\FileReaderFactory;
use App\Services\Import\ImportMappingValidationService;
use App\Services\Suppliers\SupplierProfileResolver;
use Illuminate\Console\Command;

class ValidateImportMappingCommand extends Command
{
    protected $signature = 'imports:validate-mapping
                            {import? : ID de la importación}
                            {--supplier= : Nombre o parte del nombre del proveedor (ADAGIO, GEWA, FENDER, YAMAHA...) para usar la última importación}
                            {--show-suggested : Mostrar mapeo sugerido por perfil sin guardar}
                            {--list : Listar importaciones recientes (ID, proveedor, archivo, estado, productos) y salir}';

    protected $description = 'Valida la calidad del mapeo y transformación sobre normalized_products de una importación real.';

    public function handle(
        ImportMappingValidationService $validationService,
        SupplierProfileResolver $profileResolver,
        FileReaderFactory $readerFactory
    ): int {
        $importId = $this->argument('import');
        $supplierName = $this->option('supplier');
        $listOnly = $this->option('list');

        if ($listOnly) {
            $this->listRecentImports();
            return self::SUCCESS;
        }

        if (! $importId && ! $supplierName) {
            $this->error('Indica el ID de importación (imports:validate-mapping 123) o --supplier=ADAGIO (usa la última importación de ese proveedor).');
            $this->line('Usa --list para ver importaciones recientes.');
            return self::FAILURE;
        }

        $import = null;
        if ($importId) {
            $import = SupplierImport::with('supplier')->find($importId);
        }
        if (! $import && $supplierName) {
            $import = SupplierImport::with('supplier')
                ->whereHas('supplier', fn ($q) => $q->where('name', 'like', '%' . $supplierName . '%'))
                ->latest()
                ->first();
        }

        if (! $import) {
            $this->error('No se encontró la importación.');
            $this->line('');
            $this->listRecentImports();
            return self::FAILURE;
        }

        $this->info('Importación: #' . $import->id . ' | ' . ($import->supplier->name ?? '—') . ' | ' . $import->filename_original);
        $this->newLine();

        if ($this->option('show-suggested')) {
            $this->showSuggestedMapping($import, $profileResolver, $readerFactory);
            $this->newLine();
        }

        $productCount = $import->normalizedProducts()->count();
        if ($productCount === 0) {
            $this->warn('No hay productos normalizados. ¿Has guardado el mapeo y pulsado "Procesar → productos normalizados"?');
            $this->line('  Flujo: Subir archivo → Preview → Mapeo (revisar/guardar) → Procesar.');
            return self::FAILURE;
        }

        $report = $validationService->reportForImport($import);

        $this->table(
            ['Campo', 'Rellenados', 'Tasa %', 'Muestra 1', 'Muestra 2', 'Muestra 3', 'Incidencias'],
            $this->formatReportTable($report)
        );

        $this->newLine();
        $this->line('Perfil aplicado: ' . ($report['profile_logical_code'] ?? '—'));
        if (! empty($report['columns_map'])) {
            $this->line('Columnas mapeadas: ' . implode(', ', array_keys($report['columns_map'])));
        }

        return self::SUCCESS;
    }

    private function showSuggestedMapping(SupplierImport $import, SupplierProfileResolver $profileResolver, FileReaderFactory $readerFactory): void
    {
        $path = storage_path('app/' . $import->file_path);
        if (! file_exists($path)) {
            $this->warn('Archivo no encontrado en disco; no se puede calcular el mapeo sugerido.');
            return;
        }

        try {
            $reader = $readerFactory->getReaderForType($import->file_type);
            $columns = $reader->getColumnNames($path);
            $sampleRows = $reader->readRows($path, 200);
        } catch (\Throwable $e) {
            $this->warn('Error al leer archivo: ' . $e->getMessage());
            return;
        }

        $profile = $profileResolver->resolve($import->supplier, $import);
        $suggested = $profile->suggestMapping($import->supplier, $import, $columns, $sampleRows);

        $this->info('=== Mapeo sugerido por perfil (' . $profile->getLogicalCode() . ') ===');
        $rows = [];
        foreach ($suggested as $target => $source) {
            $rows[] = [$target, $source];
        }
        $this->table(['Target', 'Columna origen'], $rows);
    }

    /**
     * @param  array{total: int, fields: array<string, array{filled: int, rate: float, samples: array<int, string>, issues: array<int, string>}>}  $report
     * @return array<int, array<int, string>>
     */
    private function listRecentImports(): void
    {
        $imports = SupplierImport::with('supplier')
            ->withCount('normalizedProducts')
            ->latest()
            ->limit(30)
            ->get();

        if ($imports->isEmpty()) {
            $this->warn('No hay ninguna importación. Crea una desde el admin (Importaciones → Nueva importación).');
            return;
        }

        $this->info('Importaciones recientes (usa el ID o --supplier=nombre):');
        $this->line('');

        $rows = [];
        foreach ($imports as $i) {
            $rows[] = [
                $i->id,
                $i->supplier->name ?? '—',
                mb_strlen($i->filename_original ?? '') > 35 ? mb_substr($i->filename_original, 0, 32) . '...' : ($i->filename_original ?? '—'),
                $i->status,
                $i->normalized_products_count,
            ];
        }
        $this->table(['ID', 'Proveedor', 'Archivo', 'Estado', 'Normalized'], $rows);
        $this->line('');
        $this->line('Ejemplo: php artisan imports:validate-mapping ' . $imports->first()->id);
    }

    private function formatReportTable(array $report): array
    {
        $total = $report['total'];
        $rows = [];
        foreach ($report['fields'] as $field => $data) {
            $rows[] = [
                $field,
                (string) $data['filled'] . '/' . $total,
                (string) $data['rate'] . '%',
                $data['samples'][0] ?? '—',
                $data['samples'][1] ?? '—',
                $data['samples'][2] ?? '—',
                implode('; ', array_slice($data['issues'], 0, 3)),
            ];
        }
        return $rows;
    }
}
