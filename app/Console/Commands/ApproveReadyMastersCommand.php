<?php

namespace App\Console\Commands;

use App\Models\MasterProduct;
use Illuminate\Console\Command;

class ApproveReadyMastersCommand extends Command
{
    protected $signature = 'catalog:approve-ready-masters
                            {--limit=100 : Maximo de maestros a aprobar}
                            {--supplier-id= : Limitar a un proveedor}
                            {--dry-run : Solo contar candidatos sin aprobar}
                            {--allow-suggested-category : Permitir categoria suggested como valida}';

    protected $description = 'Aprueba maestros que ya estan listos para exportacion: categoria, precio y sin incidencias EAN abiertas.';

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $allowSuggested = (bool) $this->option('allow-suggested-category');

        $query = MasterProduct::query()
            ->where('is_approved', false)
            ->whereNotNull('price_tax_incl')
            ->whereNotNull('cost_price')
            ->whereColumn('price_tax_incl', '>=', 'cost_price')
            ->whereNotNull('category_id')
            ->when(! $allowSuggested, fn ($builder) => $builder->where('category_status', 'confirmed'))
            ->whereDoesntHave('normalizedProducts.productEanIssues', fn ($builder) => $builder->whereNull('resolved_at'))
            ->when($this->option('supplier-id'), fn ($builder) => $builder->whereHas('normalizedProducts', fn ($inner) => $inner->where('supplier_id', (int) $this->option('supplier-id'))))
            ->orderBy('id');

        $candidateCount = (clone $query)->count();

        if ($candidateCount === 0) {
            $this->warn('No hay maestros que cumplan los criterios de aprobacion segura.');
            return self::SUCCESS;
        }

        $this->info("Candidatos listos para aprobar: {$candidateCount}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $ids = $query->pluck('id');

        MasterProduct::query()
            ->whereIn('id', $ids)
            ->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by_id' => null,
            ]);

        $this->info('Maestros aprobados: ' . $ids->count());

        return self::SUCCESS;
    }
}
