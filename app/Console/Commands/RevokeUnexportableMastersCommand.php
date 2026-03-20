<?php

namespace App\Console\Commands;

use App\Services\Export\MasterApprovalService;
use Illuminate\Console\Command;

class RevokeUnexportableMastersCommand extends Command
{
    protected $signature = 'catalog:revoke-unexportable-masters
                            {--limit=100 : Maximo de maestros aprobados a retirar}
                            {--supplier-id= : Limitar a un proveedor}
                            {--dry-run : Solo contar candidatos sin modificar datos}';

    protected $description = 'Retira la aprobacion de maestros que ya no cumplen la exportacion real a PrestaShop.';

    public function handle(MasterApprovalService $masterApprovalService): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $supplierId = $this->option('supplier-id') !== null
            ? (int) $this->option('supplier-id')
            : null;

        $ids = $masterApprovalService->collectRevokableApprovedIds(
            supplierId: $supplierId,
            limit: $limit
        );
        $candidateCount = $ids->count();

        if ($candidateCount === 0) {
            $this->info('No hay maestros aprobados que deban retirarse.');
            return self::SUCCESS;
        }

        $this->warn("Maestros aprobados que ya no son exportables: {$candidateCount}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        $revokedCount = $masterApprovalService->revoke($ids);

        $this->info('Aprobaciones retiradas: ' . $revokedCount);

        return self::SUCCESS;
    }
}
