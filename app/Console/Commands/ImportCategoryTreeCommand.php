<?php

namespace App\Console\Commands;

use App\Services\Categories\CategoryTreeImportService;
use Illuminate\Console\Command;

class ImportCategoryTreeCommand extends Command
{
    protected $signature = 'categories:import-tree
                            {path : Ruta al archivo de categorías (txt, csv, tsv o json)}
                            {--truncate : Vaciar categories antes de importar}
                            {--dry-run : Simular sin guardar cambios}';

    protected $description = 'Importa el árbol maestro de categorías desde un fichero de rutas o estructura JSON.';

    public function handle(CategoryTreeImportService $importer): int
    {
        $path = $this->argument('path');

        try {
            $result = $importer->importFromFile(
                $path,
                (bool) $this->option('truncate'),
                (bool) $this->option('dry-run'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Importación de categorías completada.');
        $this->line('Rutas leídas: ' . $result['paths_total']);
        $this->line('Categorías creadas: ' . $result['created']);
        $this->line('Categorías reutilizadas: ' . $result['reused']);
        $this->line('Total en categories: ' . $result['categories_total']);

        if ($this->option('dry-run')) {
            $this->comment('Modo simulación: no se ha escrito nada en base de datos.');
        }

        return self::SUCCESS;
    }
}
