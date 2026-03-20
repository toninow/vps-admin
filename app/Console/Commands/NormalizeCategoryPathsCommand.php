<?php

namespace App\Console\Commands;

use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Support\CategoryPathFormatter;
use Illuminate\Console\Command;

class NormalizeCategoryPathsCommand extends Command
{
    protected $signature = 'catalog:normalize-category-paths';

    protected $description = 'Limpia y normaliza las rutas de categoría guardadas en productos normalizados y maestros.';

    public function handle(): int
    {
        $normalizedUpdated = 0;
        $masterUpdated = 0;

        NormalizedProduct::query()
            ->whereNotNull('category_path_export')
            ->where('category_path_export', '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($products) use (&$normalizedUpdated) {
                foreach ($products as $product) {
                    $formatted = CategoryPathFormatter::normalizeForStorage(
                        $product->category_path_export,
                        $product->name,
                        $product->summary
                    );

                    if (($formatted ?? null) === ($product->category_path_export ?? null)) {
                        continue;
                    }

                    $product->category_path_export = $formatted;
                    $product->save();
                    $normalizedUpdated++;
                }
            });

        MasterProduct::query()
            ->whereNotNull('category_path_export')
            ->where('category_path_export', '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($products) use (&$masterUpdated) {
                foreach ($products as $product) {
                    $formatted = CategoryPathFormatter::normalizeForStorage(
                        $product->category_path_export,
                        $product->name,
                        $product->summary
                    );

                    if (($formatted ?? null) === ($product->category_path_export ?? null)) {
                        continue;
                    }

                    $product->category_path_export = $formatted;
                    $product->save();
                    $masterUpdated++;
                }
            });

        $this->info("Normalized products actualizados: {$normalizedUpdated}");
        $this->info("Master products actualizados: {$masterUpdated}");

        return self::SUCCESS;
    }
}
