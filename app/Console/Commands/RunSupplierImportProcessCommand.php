<?php

namespace App\Console\Commands;

use App\Models\SupplierImport;
use App\Services\Import\ImportPostProcessAutomationService;
use App\Services\Import\ImportTransformerService;
use App\Services\Normalization\NormalizationPipelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunSupplierImportProcessCommand extends Command
{
    private const LOCK_WAIT_SECONDS = 5;
    private const LOCK_TIMEOUT_SECONDS = 21600;

    protected $signature = 'imports:run-process {import_id : ID de la importación}';

    protected $description = 'Procesa una importación en segundo plano y actualiza su progreso.';

    public function handle(
        ImportTransformerService $transformer,
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

        if (empty(data_get($import->mapping_snapshot, 'columns_map'))) {
            $import->update([
                'pipeline_status' => 'failed',
                'pipeline_stage' => 'transforming',
                'pipeline_message' => 'No hay mapeo guardado para esta importación.',
                'pipeline_finished_at' => now(),
            ]);

            $this->releasePipelineLock();
            return self::FAILURE;
        }

        $total = $import->supplierImportRows()->where('status', 'pending')->count();

        $import->update([
            'status' => 'processing',
            'pipeline_status' => 'processing',
            'pipeline_stage' => 'transforming',
            'pipeline_total' => $total,
            'pipeline_processed' => 0,
            'pipeline_percent' => 0,
            'pipeline_message' => 'Iniciando transformación...',
            'pipeline_started_at' => now(),
            'pipeline_finished_at' => null,
            'error_message' => null,
        ]);

        try {
            $result = $transformer->transformImportToNormalizedProducts($import, function (array $progress) use ($import) {
                $total = max(1, (int) ($progress['total'] ?? 0));
                $handled = (int) ($progress['handled'] ?? 0);
                $percent = round(($handled / $total) * 100, 2);

                $import->forceFill([
                    'pipeline_total' => $total,
                    'pipeline_processed' => $handled,
                    'pipeline_percent' => $percent,
                    'pipeline_message' => $progress['message'] ?? 'Procesando...',
                ])->save();
            });

            $totalHandled = ($result['created'] ?? 0) + ($result['errors'] ?? 0) + ($result['skipped'] ?? 0);
            $normalizedTotal = $import->normalizedProducts()->count();

            $import->update([
                'pipeline_status' => 'processing',
                'pipeline_stage' => 'normalizing',
                'pipeline_total' => $normalizedTotal,
                'pipeline_processed' => 0,
                'pipeline_percent' => 0,
                'pipeline_message' => 'Transformación completada. Iniciando normalización completa del catálogo...',
                'pipeline_finished_at' => null,
                'error_message' => null,
            ]);

            $pipeline->runForImport($import->fresh(), function (array $progress) use ($import) {
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
                'pipeline_total' => $normalizedTotal,
                'pipeline_processed' => $normalizedTotal,
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
            $processedProducts = $latestRun?->processed_products ?? $normalizedTotal;
            $percentComplete = $latestRun?->percent_complete ?? 100;

            $import->update([
                'status' => 'processed',
                'pipeline_status' => 'completed',
                'pipeline_stage' => 'finalizing',
                'pipeline_total' => $normalizedTotal,
                'pipeline_processed' => $processedProducts,
                'pipeline_percent' => max(100, (float) $percentComplete),
                'pipeline_message' => sprintf(
                    'Carga completa finalizada: %d productos importados y normalizados, %d maestros consolidados y %d aprobados automáticamente.',
                    $processedProducts,
                    (int) ($automation['masters_in_import'] ?? 0),
                    (int) ($automation['approved_masters'] ?? 0)
                ),
                'pipeline_finished_at' => now(),
                'error_message' => null,
            ]);

            $this->info(sprintf(
                'Importación #%d completada. Creados: %d, errores: %d, omitidos: %d.',
                $import->id,
                $result['created'] ?? 0,
                $result['errors'] ?? 0,
                $result['skipped'] ?? 0
            ));

            $this->releasePipelineLock();
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'pipeline_status' => 'failed',
                'pipeline_stage' => $import->pipeline_stage ?: 'transforming',
                'pipeline_message' => 'La importación falló durante la carga completa.',
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
                'pipeline_stage' => 'transforming',
                'pipeline_percent' => 0,
                'pipeline_message' => 'Esperando turno en cola. Hay otro lote pesado en ejecución.',
                'pipeline_started_at' => null,
                'pipeline_finished_at' => null,
                'error_message' => null,
            ])->save();

            if ($waited >= self::LOCK_TIMEOUT_SECONDS) {
                $import->update([
                    'pipeline_status' => 'failed',
                    'pipeline_stage' => 'transforming',
                    'pipeline_message' => 'La importación superó el tiempo máximo de espera en cola.',
                    'pipeline_finished_at' => now(),
                    'error_message' => 'Tiempo máximo de espera agotado mientras se esperaba turno de procesamiento.',
                ]);

                $this->error('Tiempo máximo de espera agotado mientras la importación esperaba turno.');

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
