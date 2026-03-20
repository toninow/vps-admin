<?php

namespace App\Services\Normalization;

use App\Models\DuplicateProductGroup;
use App\Models\DuplicateProductGroupItem;
use App\Models\NormalizedProduct;

class DuplicateDetectionService
{
    /**
     * Detecta duplicados por EAN y sugiere por similitud (brand + supplier_reference + name).
     * No unifica automáticamente.
     */
    public function detectForProducts(array $normalizedProductIds): array
    {
        $products = NormalizedProduct::whereIn('id', $normalizedProductIds)->get();
        $byEan = [];
        foreach ($products as $p) {
            $ean = $this->normalizeEanForGroup($p->ean13);
            if ($ean !== '') {
                $byEan[$ean][] = $p;
            }
        }

        $groupsCreated = 0;
        foreach ($byEan as $ean => $list) {
            if (count($list) < 2) {
                continue;
            }
            $group = DuplicateProductGroup::firstOrCreate(
                ['ean13' => $ean],
                ['status' => 'pending_review']
            );
            foreach ($list as $np) {
                DuplicateProductGroupItem::firstOrCreate(
                    [
                        'duplicate_product_group_id' => $group->id,
                        'normalized_product_id' => $np->id,
                    ],
                    ['master_product_id' => $np->master_product_id]
                );
            }
            $groupsCreated++;
        }

        $similaritySignals = $this->detectSimilaritySignals($products);

        return [
            'groups_by_ean' => $groupsCreated,
            'similarity_pairs' => $similaritySignals,
        ];
    }

    protected function normalizeEanForGroup($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        return strlen($digits) === 13 ? $digits : '';
    }

    /**
     * Señales de posible duplicado por marca + referencia + nombre similar (sin crear grupo automático).
     *
     * @param  \Illuminate\Support\Collection<int, NormalizedProduct>  $products
     * @return array<int, array{id_a: int, id_b: int, score: float}>
     */
    protected function detectSimilaritySignals($products): array
    {
        $signals = [];
        $list = $products->values()->all();
        for ($i = 0; $i < count($list); $i++) {
            for ($j = $i + 1; $j < count($list); $j++) {
                $a = $list[$i];
                $b = $list[$j];
                if ($a->ean13 !== null && $a->ean13 !== '' && $b->ean13 !== null && $b->ean13 !== '') {
                    continue;
                }
                $score = $this->similarityScore($a, $b);
                if ($score >= 0.5) {
                    $signals[] = ['id_a' => $a->id, 'id_b' => $b->id, 'score' => round($score, 4)];
                }
            }
        }
        return $signals;
    }

    protected function similarityScore(NormalizedProduct $a, NormalizedProduct $b): float
    {
        $score = 0;
        $weight = 0;

        if ($a->brand !== null && $b->brand !== null) {
            $weight += 0.3;
            if (mb_strtolower(trim($a->brand)) === mb_strtolower(trim($b->brand))) {
                $score += 0.3;
            }
        }

        if ($a->supplier_reference !== null && $b->supplier_reference !== null) {
            $weight += 0.4;
            if (trim($a->supplier_reference) === trim($b->supplier_reference)) {
                $score += 0.4;
            }
        }

        $nameA = mb_strtolower(trim($a->name ?? ''));
        $nameB = mb_strtolower(trim($b->name ?? ''));
        if ($nameA !== '' && $nameB !== '') {
            $weight += 0.3;
            similar_text($nameA, $nameB, $pct);
            $score += 0.3 * ($pct / 100);
        }

        return $weight > 0 ? $score / $weight : 0;
    }
}
