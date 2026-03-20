<?php

namespace App\Console\Commands;

use App\Models\NormalizedProduct;
use App\Services\Normalization\DefaultCategorySuggestionApplierService;
use Illuminate\Console\Command;

class ApplyDefaultCategorySuggestionsCommand extends Command
{
    protected $signature = 'categories:apply-default-suggestions
                            {--min-score=15 : Score mínimo para aplicar la sugerencia}
                            {--supplier-id= : Limitar a un proveedor concreto}';

    protected $description = 'Aplica la mejor sugerencia de categoría como valor por defecto, pendiente de validación final en la app.';

    public function handle(DefaultCategorySuggestionApplierService $applier): int
    {
        $minScore = (float) $this->option('min-score');
        $supplierId = $this->option('supplier-id');
        $productIds = null;
        if ($supplierId) {
            $productIds = NormalizedProduct::query()
                ->where('supplier_id', (int) $supplierId)
                ->pluck('id')
                ->all();
        }

        $result = $applier->applyForProducts($productIds, $minScore);
        $applied = $result['applied'] ?? 0;
        $updatedMasters = $result['updated_masters'] ?? 0;

        $this->info("Sugerencias por defecto aplicadas: {$applied}");
        $this->info("Maestros actualizados: {$updatedMasters}");
        $this->info('Estado guardado como category_status = suggested; la app podrá confirmar la ruta final.');

        return self::SUCCESS;
    }
}
