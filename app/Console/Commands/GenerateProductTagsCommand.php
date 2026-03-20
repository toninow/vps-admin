<?php

namespace App\Console\Commands;

use App\Models\NormalizedProduct;
use App\Services\Normalization\AdvancedNormalizationService;
use Illuminate\Console\Command;

class GenerateProductTagsCommand extends Command
{
    protected $signature = 'products:generate-tags
                            {--chunk=500 : Tamaño de lote}
                            {--supplier-id= : Limitar a un proveedor}
                            {--only-missing : Solo productos sin tags}';

    protected $description = 'Genera etiquetas para normalized_products a partir de nombre, marca, referencia, resumen y ruta.';

    public function handle(AdvancedNormalizationService $normalizationService): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $supplierId = $this->option('supplier-id');

        $query = NormalizedProduct::query()->orderBy('id');

        if ($supplierId) {
            $query->where('supplier_id', (int) $supplierId);
        }

        if ($this->option('only-missing')) {
            $query->where(function ($q) {
                $q->whereNull('tags')->orWhere('tags', '');
            });
        }

        $ids = $query->pluck('id');
        $total = $ids->count();

        if ($total === 0) {
            $this->warn('No hay productos para procesar.');
            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($ids->chunk($chunk) as $batch) {
            $result = $normalizationService->normalize($batch->all());
            $updated += (int) ($result['updated'] ?? 0);

            $this->line(sprintf(
                'Lote procesado: %d productos | actualizados +%d',
                $result['total'] ?? 0,
                $result['updated'] ?? 0
            ));
        }

        $this->newLine();
        $this->info("Etiquetas generadas/revisadas en {$total} productos.");
        $this->line("Productos actualizados: {$updated}");

        return self::SUCCESS;
    }
}
