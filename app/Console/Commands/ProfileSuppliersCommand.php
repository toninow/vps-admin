<?php

namespace App\Console\Commands;

use App\Services\SupplierProfiling\SupplierFileProfiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProfileSuppliersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:profile {path=/home/antonio/Documents/PROVEEDORES_CSV_2026}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analiza archivos de proveedores (CSV/XLSX/XML) y genera un informe de profiling.';

    public function handle(SupplierFileProfiler $profiler): int
    {
        $path = $this->argument('path');

        $this->info("Analizando carpeta: {$path}");

        $result = $profiler->profileDirectory($path);

        $timestamp = now()->format('Ymd_His');
        $dir = 'supplier-profiling';
        $filename = "profiling_{$timestamp}.json";
        $fullPath = $dir . '/' . $filename;

        Storage::disk('local')->put($fullPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Informe JSON guardado en storage/app/{$fullPath}");

        $summary = $result['summary'] ?? [];
        $this->line('');
        $this->line('=== Resumen global ===');
        $this->line('Total de archivos analizados: ' . ($summary['total_files'] ?? 0));

        foreach ($summary['suppliers'] ?? [] as $code => $data) {
            $this->line('');
            $this->line("Proveedor: {$code}");
            $this->line('  Archivos: ' . ($data['files'] ?? 0));
            $this->line('  Filas totales: ' . ($data['total_rows'] ?? 0));
            $this->line('  Columnas EAN detectadas: ' . implode(', ', $data['ean_columns_detected'] ?? []));
        }

        $this->line('');
        $this->line('Ejemplo de primer archivo analizado (si existe):');
        $first = $result['files'][0] ?? null;
        if ($first) {
            $this->line(json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line('No se encontraron archivos analizables en la carpeta indicada.');
        }

        return Command::SUCCESS;
    }
}

