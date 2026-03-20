<?php

namespace App\Console\Commands;

use App\Models\NormalizedProduct;
use App\Services\Normalization\CategorySuggestionService;
use Illuminate\Console\Command;

class SuggestNormalizedCategoriesCommand extends Command
{
    protected $signature = 'categories:suggest-normalized
                            {--missing-only : Solo productos sin category_id}
                            {--chunk=500 : Tamaño de lote}
                            {--limit=0 : Límite máximo de productos a procesar}';

    protected $description = 'Genera sugerencias de categoría para normalized_products y autoasigna cuando el score es alto.';

    public function handle(CategorySuggestionService $suggestionService): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));

        $query = NormalizedProduct::query()->orderBy('id');
        if ($this->option('missing-only')) {
            $query->whereNull('category_id');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $ids = $query->pluck('id');
        $total = $ids->count();

        if ($total === 0) {
            $this->warn('No hay productos que procesar.');
            return self::SUCCESS;
        }

        $this->info('Procesando ' . $total . ' productos normalizados para sugerencias de categoría.');

        $stats = [
            'suggestions_created' => 0,
            'auto_assigned' => 0,
            'processed' => 0,
        ];

        foreach ($ids->chunk($chunk) as $batch) {
            $result = $suggestionService->suggestForProducts($batch->all());
            $stats['suggestions_created'] += (int) ($result['suggestions_created'] ?? 0);
            $stats['auto_assigned'] += (int) ($result['auto_assigned'] ?? 0);
            $stats['processed'] += (int) ($result['total_products'] ?? 0);

            $this->line(sprintf(
                'Lote procesado: %d productos | sugerencias +%d | autoasignados +%d',
                $result['total_products'] ?? 0,
                $result['suggestions_created'] ?? 0,
                $result['auto_assigned'] ?? 0,
            ));
        }

        $this->newLine();
        $this->info('Sugerencias completadas.');
        $this->line('Productos procesados: ' . $stats['processed']);
        $this->line('Sugerencias creadas: ' . $stats['suggestions_created']);
        $this->line('Autoasignados: ' . $stats['auto_assigned']);

        return self::SUCCESS;
    }
}
