<?php

namespace App\Services\Import;

use App\Models\DuplicateProductGroup;
use App\Models\MasterProduct;
use App\Models\NormalizationRun;
use App\Models\ProductEanIssue;
use App\Models\ProductImage;
use App\Models\SupplierImport;
use App\Models\SupplierImportRow;
use Illuminate\Support\Facades\DB;

class ImportPipelineResetService
{
    /**
     * Elimina lo generado por el proceso del lote y deja la importación lista para relanzar.
     *
     * @return array{normalized_deleted:int, master_deleted:int}
     */
    public function resetToMappingState(SupplierImport $import): array
    {
        $normalizedIds = $import->normalizedProducts()->pluck('id')->all();
        $masterIds = $import->normalizedProducts()
            ->whereNotNull('master_product_id')
            ->pluck('master_product_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalizedDeleted = count($normalizedIds);
        $deletedMasters = 0;

        DB::transaction(function () use ($import, $normalizedIds, $masterIds, &$deletedMasters) {
            if ($normalizedIds !== []) {
                ProductEanIssue::query()
                    ->whereIn('normalized_product_id', $normalizedIds)
                    ->delete();

                ProductImage::query()
                    ->whereIn('normalized_product_id', $normalizedIds)
                    ->delete();
            }

            NormalizationRun::query()
                ->where('import_id', $import->id)
                ->delete();

            $import->normalizedProducts()->delete();

            DuplicateProductGroup::query()
                ->whereNull('master_product_id')
                ->whereDoesntHave('duplicateProductGroupItems')
                ->delete();

            if ($masterIds !== []) {
                $orphanMasters = MasterProduct::query()
                    ->whereIn('id', $masterIds)
                    ->where('is_approved', false)
                    ->doesntHave('normalizedProducts')
                    ->doesntHave('masterProductSuppliers')
                    ->doesntHave('productImages')
                    ->doesntHave('stockChanges')
                    ->doesntHave('stockScanEvents')
                    ->get();

                $deletedMasters = $orphanMasters->count();
                foreach ($orphanMasters as $master) {
                    $master->delete();
                }
            }

            SupplierImportRow::query()
                ->where('supplier_import_id', $import->id)
                ->update([
                    'normalized_data' => null,
                    'status' => SupplierImportRow::STATUS_PENDING,
                    'error_message' => null,
                ]);

            $import->update([
                'status' => 'mapping',
                'pipeline_status' => 'idle',
                'pipeline_stage' => null,
                'pipeline_total' => 0,
                'pipeline_processed' => 0,
                'pipeline_percent' => 0,
                'pipeline_message' => null,
                'pipeline_started_at' => null,
                'pipeline_finished_at' => null,
                'processed_rows' => 0,
                'error_rows' => 0,
                'error_message' => null,
                'finished_at' => null,
            ]);
        });

        return [
            'normalized_deleted' => $normalizedDeleted,
            'master_deleted' => $deletedMasters,
        ];
    }
}
