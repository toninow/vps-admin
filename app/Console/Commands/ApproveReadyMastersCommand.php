<?php

namespace App\Console\Commands;

use App\Services\Export\MasterApprovalService;
use Illuminate\Console\Command;

class ApproveReadyMastersCommand extends Command
{
    protected $signature = 'catalog:approve-ready-masters
                            {--limit=100 : Maximo de maestros a aprobar}
                            {--supplier-id= : Limitar a un proveedor}
                            {--dry-run : Solo contar candidatos sin aprobar}
                            {--allow-suggested-category : Permitir categoria suggested como valida}';

    protected $description = 'Aprueba maestros que ya estan listos para exportacion: categoria, precio y sin incidencias EAN abiertas.';

    public function handle(MasterApprovalService $masterApprovalService): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $allowSuggested = (bool) $this->option('allow-suggested-category');
        $supplierId = $this->option('supplier-id') !== null
            ? (int) $this->option('supplier-id')
            : null;

        $ids = $masterApprovalService->collectApprovableIds(
            allowSuggestedCategory: $allowSuggested,
            supplierId: $supplierId,
            limit: $limit
        );
        $candidateCount = $ids->count();

        if ($candidateCount === 0) {
            $this->warn('No hay maestros que cumplan los criterios de aprobacion segura.');
            return self::SUCCESS;
        }

        $this->info("Candidatos listos para aprobar: {$candidateCount}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        $approvedCount = $masterApprovalService->approve($ids);

        $this->info('Maestros aprobados: ' . $approvedCount);

        return self::SUCCESS;
    }
}
