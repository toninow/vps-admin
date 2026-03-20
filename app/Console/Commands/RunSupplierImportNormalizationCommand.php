<?php

namespace App\Console\Commands;

use App\Models\SupplierImport;
use App\Services\Import\ImportPostProcessAutomationService;
use App\Services\Normalization\NormalizationPipelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunSupplierImportNormalizationCommand extends Command
{
    private const LOCK_WAIT_SECONDS = 5;
    private const LOCK_TIMEOUT_SECONDS = 21600;

    protected $signature = 'imports:run-normalization {import_id : ID de la importación}';

    protected $description = 'Ejecuta la normalización avanzada de una importación en segundo plano.';

    public function handle(
        NormalizationPipelineService $pipeline,
        ImportPostProcessAutomationService $postProcessAutomation
    ): int
    {
        $import = SupplierImport::find($this->argument('import_id'));

        if (! $import) {
            $this->error('Importación no encontrada.');
            return self::FAILURE;
        }

        if (! $this->waitForPipelineLock($import)) {
            return self::FAILURE;
        }

        if ($import->status !== 'processed') {
            $import->update([
                'pipeline_status' => 'failed',
                'pipeline_stage' => 'normalizing',
                'pipeline_message' => 'Solo se puede normalizar una importación ya procesada.',
                'pipeline_finished_at' => now(),
            ]);

            $this->releasePipelineLock();
            return self::FAILURE;
        }

        $totalProducts = $import->normalizedProducts()->count();

        $import->update([
            'pipeline_status' => 'processing',
            'pipeline_stage' => 'normalizing',
            'pipeline_total' => $totalProducts,
            'pipeline_processed' => 0,
            'pipeline_percent' => 0,
            'pipeline_message' => 'Ejecutando normalización avanzada...',
            'pipeline_started_at' => now(),
            'pipeline_finished_at' => null,
            'error_message' => null,
        ]);

        try {
            $pipeline->runForImport($import, function (array $progress) use ($import) {
                $import->forceFill([
                    'pipeline_stage' => $progress['stage'] ?? 'normalizing',
                    'pipeline_total' => (int) ($progress['total'] ?? 0),
                    'pipeline_processed' => (int) ($progress['processed'] ?? 0),
                    'pipeline_percent' => (float) ($progress['percent'] ?? 0),
                    'pipeline_message' => $progress['message'] ?? 'Normalizando productos...',
                ])->save();
            });

            $import->refresh();
            $import->update([
                'pipeline_status' => 'processing',
                'pipeline_stage' => 'finalizing',
                'pipeline_total' => $totalProducts,
                'pipeline_processed' => $totalProducts,
                'pipeline_percent' => 90,
                'pipeline_message' => 'Consolidando maestros, sincronizando revisión y aprobando lo seguro...',
                'pipeline_finished_at' => null,
                'error_message' => null,
            ]);

            $automation = $postProcessAutomation->finalizeImport($import->fresh(), function (array $progress) use ($import) {
                $import->forceFill([
                    'pipeline_stage' => $progress['stage'] ?? 'finalizing',
                    'pipeline_total' => (int) ($progress['total'] ?? 0),
                    'pipeline_processed' => (int) ($progress['processed'] ?? 0),
                    'pipeline_percent' => (float) ($progress['percent'] ?? 0),
                    'pipeline_message' => $progress['message'] ?? 'Cerrando la automatización del lote...',
                ])->save();
            });

            $import->refresh();
            $latestRun = $import->normalizationRuns()->latest('id')->first();
            $processed = $latestRun?->processed_products ?? $totalProducts;
            $percent = $latestRun?->percent_complete ?? 100;

            $import->update([
                'pipeline_status' => 'completed',
                'pipeline_stage' => 'finalizing',
                'pipeline_total' => $totalProducts,
                'pipeline_processed' => $processed,
                'pipeline_percent' => max(100, (float) $percent),
                'pipeline_message' => sprintf(
                    'Normalización y cierre automático completados: %d maestros consolidados y %d aprobados automáticamente.',
                    (int) ($automation['masters_in_import'] ?? 0),
                    (int) ($automation['approved_masters'] ?? 0)
                ),
                'pipeline_finished_at' => now(),
                'error_message' => null,
            ]);

            $this->info("Normalización completada para la importación #{$import->id}.");

            $this->releasePipelineLock();
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $import->update([
                'pipeline_status' => 'failed',
                'pipeline_stage' => 'normalizing',
                'pipeline_message' => 'La normalización avanzada falló.',
                'pipeline_finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());

            $this->releasePipelineLock();
            return self::FAILURE;
        }
    }

    private function waitForPipelineLock(SupplierImport $import): bool
    {
        $waited = 0;

        while (true) {
            if ($this->acquirePipelineLock()) {
                return true;
            }

            $waited += self::LOCK_WAIT_SECONDS;

            $import->forceFill([
                'pipeline_status' => 'queued',
                'pipeline_stage' => 'normalizing',
                'pipeline_percent' => 0,
                'pipeline_message' => 'Esperando turno en cola. Hay otro lote pesado en ejecución.',
                'pipeline_started_at' => null,
                'pipeline_finished_at' => null,
                'error_message' => null,
            ])->save();

            if ($waited >= self::LOCK_TIMEOUT_SECONDS) {
                $import->update([
                    'pipeline_status' => 'failed',
                    'pipeline_stage' => 'normalizing',
                    'pipeline_message' => 'La normalización superó el tiempo máximo de espera en cola.',
                    'pipeline_finished_at' => now(),
                    'error_message' => 'Tiempo máximo de espera agotado mientras se esperaba turno de normalización.',
                ]);

                $this->error('Tiempo máximo de espera agotado mientras la normalización esperaba turno.');

                return false;
            }

            sleep(self::LOCK_WAIT_SECONDS);
        }
    }

    private function acquirePipelineLock(): bool
    {
        $row = DB::selectOne('SELECT GET_LOCK(?, 0) as acquired', ['mpsfp-import-pipeline']);

        return (int) ($row->acquired ?? 0) === 1;
    }

    private function releasePipelineLock(): void
    {
        try {
            DB::selectOne('SELECT RELEASE_LOCK(?) as released', ['mpsfp-import-pipeline']);
        } catch (\Throwable $e) {
            // No bloqueamos el flujo si el release falla al final del proceso.
        }
    }
}
