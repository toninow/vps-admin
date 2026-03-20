<?php

namespace App\Services\Normalization;

use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Models\ProductCategorySuggestion;
use App\Services\Categories\CategoryPathBuilderService;
use Illuminate\Support\Facades\DB;

class DefaultCategorySuggestionApplierService
{
    public function __construct(
        protected CategoryPathBuilderService $pathBuilder,
    ) {}

    /**
     * Aplica la mejor sugerencia como ruta/categoría por defecto.
     * Mantiene el estado como `suggested` para que la app pueda confirmarlo después.
     *
     * @param  array<int>|null  $normalizedProductIds
     * @return array{applied:int, updated_masters:int}
     */
    public function applyForProducts(?array $normalizedProductIds = null, float $minScore = 15.0): array
    {
        $normalizedProductIds = $normalizedProductIds !== null
            ? array_values(array_unique(array_map('intval', $normalizedProductIds)))
            : null;

        $suggestionsQuery = ProductCategorySuggestion::query()
            ->with(['normalizedProduct.masterProduct', 'category'])
            ->where('score', '>=', $minScore)
            ->when(
                $normalizedProductIds !== null && $normalizedProductIds !== [],
                fn ($query) => $query->whereIn('normalized_product_id', $normalizedProductIds)
            )
            ->whereIn('id', function ($sub) use ($minScore, $normalizedProductIds) {
                $sub->from('product_category_suggestions as pcs')
                    ->selectRaw('MIN(pcs.id)')
                    ->join(DB::raw('(SELECT normalized_product_id, MAX(score) as top_score FROM product_category_suggestions GROUP BY normalized_product_id) tops'), function ($join) {
                        $join->on('tops.normalized_product_id', '=', 'pcs.normalized_product_id')
                            ->on('tops.top_score', '=', 'pcs.score');
                    })
                    ->join('normalized_products as np', 'np.id', '=', 'pcs.normalized_product_id')
                    ->where('pcs.score', '>=', $minScore)
                    ->when(
                        $normalizedProductIds !== null && $normalizedProductIds !== [],
                        fn ($query) => $query->whereIn('np.id', $normalizedProductIds)
                    )
                    ->groupBy('pcs.normalized_product_id');
            });

        $applied = 0;
        $updatedMasters = 0;

        $suggestionsQuery->chunkById(500, function ($suggestions) use (&$applied, &$updatedMasters) {
            foreach ($suggestions as $suggestion) {
                if (! $suggestion->normalizedProduct || ! $suggestion->category) {
                    continue;
                }

                /** @var NormalizedProduct $product */
                $product = $suggestion->normalizedProduct;

                if (($product->category_status ?? 'unassigned') === 'confirmed') {
                    continue;
                }

                $path = $this->pathBuilder->buildExportPath($suggestion->category);

                DB::transaction(function () use ($product, $suggestion, $path, &$applied, &$updatedMasters) {
                    $product->update([
                        'category_id' => $suggestion->category_id,
                        'category_status' => 'suggested',
                        'category_path_export' => $path,
                    ]);

                    if ($product->masterProduct && ($product->masterProduct->category_status ?? 'unassigned') !== 'confirmed') {
                        /** @var MasterProduct $master */
                        $master = $product->masterProduct;
                        $master->update([
                            'category_id' => $suggestion->category_id,
                            'category_status' => 'suggested',
                            'category_path_export' => $path,
                        ]);
                        $updatedMasters++;
                    }

                    ProductCategorySuggestion::query()
                        ->where('normalized_product_id', $product->id)
                        ->update(['accepted_at' => null]);

                    $applied++;
                });
            }
        });

        return [
            'applied' => $applied,
            'updated_masters' => $updatedMasters,
        ];
    }
}
