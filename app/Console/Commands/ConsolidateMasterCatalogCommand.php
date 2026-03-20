<?php

namespace App\Console\Commands;

use App\Models\MasterProduct;
use App\Models\NormalizedProduct;
use App\Services\Identity\ProductIdentityEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateMasterCatalogCommand extends Command
{
    protected $signature = 'catalog:consolidate-masters
                            {--chunk=250 : Tamaño de lote}
                            {--only-unlinked : Solo productos sin master_product_id}
                            {--supplier-id= : Limitar a un proveedor concreto}';

    protected $description = 'Consolida normalized_products en master_products de forma masiva usando el motor de identidad.';

    public function handle(ProductIdentityEngine $identityEngine): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $initialQuery = $this->scopeQuery(includeLinked: ! $this->option('only-unlinked'));
        $total = (clone $initialQuery)->count();

        if ($total === 0) {
            $this->warn('No hay productos normalizados para consolidar.');
            return self::SUCCESS;
        }

        $this->info("Consolidando {$total} productos normalizados en catálogo maestro...");

        $mastersBefore = MasterProduct::count();
        $bulkEanStats = $this->bulkLinkValidEans();
        $bulkRemainingStats = $this->bulkCreateRemainingMasters();
        $processed = (int) (($bulkEanStats['linked_products'] ?? 0) + ($bulkRemainingStats['linked_products'] ?? 0));
        $failures = 0;

        if ($bulkEanStats['linked_products'] > 0 || $bulkEanStats['masters_created'] > 0) {
            $this->line(sprintf(
                'Prepasada EAN: enlazados %d | masters creados %d',
                $bulkEanStats['linked_products'],
                $bulkEanStats['masters_created']
            ));
        }

        if ($bulkRemainingStats['linked_products'] > 0 || $bulkRemainingStats['masters_created'] > 0) {
            $this->line(sprintf(
                'Resto del catalogo: enlazados %d | masters creados %d',
                $bulkRemainingStats['linked_products'],
                $bulkRemainingStats['masters_created']
            ));
        }

        $remainingQuery = $this->scopeQuery(includeLinked: false);
        $remainingQuery->chunkById($chunk, function ($products) use (&$processed, &$failures, $total, $identityEngine) {
            foreach ($products as $product) {
                try {
                    $identityEngine->resolveMasterProduct($product);
                    $processed++;
                } catch (\Throwable $e) {
                    $failures++;
                    $this->error("Fallo al consolidar normalized_product #{$product->id}: {$e->getMessage()}");
                }
            }

            $this->line(sprintf(
                'Lote procesado: %d/%d | fallos: %d | masters actuales: %d',
                $processed,
                $total,
                $failures,
                MasterProduct::count()
            ));
        });

        $mastersAfter = MasterProduct::count();
        $mastersCreated = max(0, $mastersAfter - $mastersBefore);

        $this->newLine();
        $this->info('Consolidación completada.');
        $this->line("Normalizados procesados: {$processed}");
        $this->line("Fallos: {$failures}");
        $this->line("Masters creados: {$mastersCreated}");
        $this->line("Masters totales: {$mastersAfter}");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function scopeQuery(bool $includeLinked): Builder
    {
        return NormalizedProduct::query()
            ->orderBy('id')
            ->when(! $includeLinked, fn (Builder $query) => $query->whereNull('master_product_id'))
            ->when($this->option('supplier-id'), fn (Builder $query) => $query->where('supplier_id', (int) $this->option('supplier-id')));
    }

    /**
     * Hace una pasada masiva por EAN valido para evitar crear uno a uno los casos evidentes.
     *
     * @return array{linked_products:int, masters_created:int}
     */
    protected function bulkLinkValidEans(): array
    {
        $supplierId = $this->option('supplier-id') ? (int) $this->option('supplier-id') : null;
        $scopeBindings = [];
        $scopeFilters = $this->validEanFilters('np');
        $updateFilters = $this->validEanFilters('np');

        if ($supplierId !== null) {
            $scopeFilters[] = 'np.supplier_id = ?';
            $updateFilters[] = 'np.supplier_id = ?';
            $scopeBindings[] = $supplierId;
            $updateBindings[] = $supplierId;
        } else {
            $updateBindings = [];
        }

        $mastersBefore = MasterProduct::count();

        $insertSql = "
            INSERT INTO master_products (
                ean13,
                reference,
                name,
                summary,
                description,
                quantity,
                price_tax_incl,
                cost_price,
                tax_rule_id,
                warehouse,
                active,
                brand,
                category_id,
                category_status,
                category_path_export,
                tags,
                search_keywords_normalized,
                is_approved,
                version,
                created_at,
                updated_at
            )
            SELECT
                seed.ean13,
                NULLIF(TRIM(seed.supplier_reference), ''),
                seed.name,
                NULLIF(seed.summary, ''),
                NULLIF(seed.description, ''),
                0,
                seed.price_tax_incl,
                seed.cost_price,
                COALESCE(seed.tax_rule_id, 1),
                COALESCE(NULLIF(seed.warehouse, ''), 'CARPETANA'),
                COALESCE(seed.active, 1),
                NULLIF(seed.brand, ''),
                seed.category_id,
                COALESCE(NULLIF(seed.category_status, ''), CASE WHEN seed.category_id IS NULL THEN 'unassigned' ELSE 'suggested' END),
                NULLIF(seed.category_path_export, ''),
                NULLIF(seed.tags, ''),
                NULLIF(TRIM(LOWER(REGEXP_REPLACE(CONCAT_WS(' ', seed.name, seed.brand, seed.tags, seed.supplier_reference), '[^[:alnum:][:space:]]', ' '))), ''),
                0,
                1,
                NOW(),
                NOW()
            FROM normalized_products seed
            INNER JOIN (
                SELECT MIN(np.id) AS picked_id
                FROM normalized_products np
                WHERE " . implode(' AND ', $scopeFilters) . "
                GROUP BY np.ean13
            ) picks ON picks.picked_id = seed.id
            LEFT JOIN master_products mp ON mp.ean13 = seed.ean13
            WHERE mp.id IS NULL
        ";

        DB::statement($insertSql, $scopeBindings);

        $updateSql = "
            UPDATE normalized_products np
            INNER JOIN master_products mp ON mp.ean13 = np.ean13
            SET np.master_product_id = mp.id
            WHERE " . implode(' AND ', $updateFilters) . "
        ";

        $linkedProducts = DB::update($updateSql, $updateBindings);

        $supplierInsertSql = "
            INSERT INTO master_product_suppliers (
                master_product_id,
                normalized_product_id,
                supplier_id,
                supplier_reference,
                is_primary,
                created_at,
                updated_at
            )
            SELECT
                np.master_product_id,
                np.id,
                np.supplier_id,
                np.supplier_reference,
                0,
                NOW(),
                NOW()
            FROM normalized_products np
            LEFT JOIN master_product_suppliers mps ON mps.normalized_product_id = np.id
            WHERE " . implode(' AND ', $updateFilters) . "
              AND mps.id IS NULL
        ";

        DB::statement($supplierInsertSql, $updateBindings);

        return [
            'linked_products' => $linkedProducts,
            'masters_created' => max(0, MasterProduct::count() - $mastersBefore),
        ];
    }

    /**
     * Para los productos que siguen sin master tras la pasada EAN, crea un maestro 1:1
     * y los enlaza de forma masiva. Es mas seguro que forzar similitud difusa.
     *
     * @return array{linked_products:int, masters_created:int}
     */
    protected function bulkCreateRemainingMasters(): array
    {
        $supplierId = $this->option('supplier-id') ? (int) $this->option('supplier-id') : null;
        $bindings = [];
        $filters = [
            'np.master_product_id IS NULL',
        ];

        if ($supplierId !== null) {
            $filters[] = 'np.supplier_id = ?';
            $bindings[] = $supplierId;
        }

        $mastersBefore = MasterProduct::count();

        $insertSql = "
            INSERT INTO master_products (
                ean13,
                seed_normalized_product_id,
                reference,
                name,
                summary,
                description,
                quantity,
                price_tax_incl,
                cost_price,
                tax_rule_id,
                warehouse,
                active,
                brand,
                category_id,
                category_status,
                category_path_export,
                tags,
                search_keywords_normalized,
                is_approved,
                version,
                created_at,
                updated_at
            )
            SELECT
                NULL,
                np.id,
                NULLIF(TRIM(np.supplier_reference), ''),
                COALESCE(NULLIF(np.name, ''), NULLIF(np.summary, ''), CONCAT('Producto ', np.id)),
                NULLIF(np.summary, ''),
                NULLIF(np.description, ''),
                0,
                np.price_tax_incl,
                np.cost_price,
                COALESCE(np.tax_rule_id, 1),
                COALESCE(NULLIF(np.warehouse, ''), 'CARPETANA'),
                COALESCE(np.active, 1),
                NULLIF(np.brand, ''),
                np.category_id,
                COALESCE(NULLIF(np.category_status, ''), CASE WHEN np.category_id IS NULL THEN 'unassigned' ELSE 'suggested' END),
                NULLIF(np.category_path_export, ''),
                NULLIF(np.tags, ''),
                NULLIF(TRIM(LOWER(REGEXP_REPLACE(CONCAT_WS(' ', np.name, np.brand, np.tags, np.supplier_reference), '[^[:alnum:][:space:]]', ' '))), ''),
                0,
                1,
                NOW(),
                NOW()
            FROM normalized_products np
            LEFT JOIN master_products mp ON mp.seed_normalized_product_id = np.id
            WHERE " . implode(' AND ', $filters) . "
              AND mp.id IS NULL
        ";

        DB::statement($insertSql, $bindings);

        $updateSql = "
            UPDATE normalized_products np
            INNER JOIN master_products mp ON mp.seed_normalized_product_id = np.id
            SET np.master_product_id = mp.id
            WHERE " . implode(' AND ', $filters) . "
        ";

        $linkedProducts = DB::update($updateSql, $bindings);

        $supplierInsertSql = "
            INSERT INTO master_product_suppliers (
                master_product_id,
                normalized_product_id,
                supplier_id,
                supplier_reference,
                is_primary,
                created_at,
                updated_at
            )
            SELECT
                np.master_product_id,
                np.id,
                np.supplier_id,
                np.supplier_reference,
                0,
                NOW(),
                NOW()
            FROM normalized_products np
            LEFT JOIN master_product_suppliers mps ON mps.normalized_product_id = np.id
            WHERE " . implode(' AND ', $filters) . "
              AND np.master_product_id IS NOT NULL
              AND mps.id IS NULL
        ";

        DB::statement($supplierInsertSql, $bindings);

        return [
            'linked_products' => $linkedProducts,
            'masters_created' => max(0, MasterProduct::count() - $mastersBefore),
        ];
    }

    /**
     * @return string[]
     */
    protected function validEanFilters(string $alias): array
    {
        return [
            "{$alias}.ean13 IS NOT NULL",
            "{$alias}.ean13 <> ''",
            "CHAR_LENGTH({$alias}.ean13) = 13",
            "({$alias}.ean_status = 'ok' OR {$alias}.barcode_status = 'ok')",
            "{$alias}.master_product_id IS NULL",
        ];
    }
}
