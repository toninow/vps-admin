<?php

namespace App\Services\Normalization;

use App\Models\DuplicateProductGroup;
use App\Models\DuplicateProductGroupItem;
use App\Models\NormalizedProduct;

class DuplicateDetectionService
{
    protected const MAX_BUCKET_SIZE = 60;
    protected const MAX_COMPARISONS = 15000;

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
        $comparisons = 0;

        $candidates = $products
            ->filter(function (NormalizedProduct $product): bool {
                return $this->normalizeEanForGroup($product->ean13) === '';
            })
            ->values();

        $buckets = [];
        foreach ($candidates as $product) {
            $bucketKey = $this->similarityBucketKey($product);
            if ($bucketKey === null) {
                continue;
            }

            $buckets[$bucketKey][] = $product;
        }

        foreach ($buckets as $bucket) {
            if (count($bucket) < 2) {
                continue;
            }

            if (count($bucket) > self::MAX_BUCKET_SIZE) {
                continue;
            }

            $list = array_values($bucket);
            for ($i = 0; $i < count($list); $i++) {
                for ($j = $i + 1; $j < count($list); $j++) {
                    $comparisons++;
                    if ($comparisons > self::MAX_COMPARISONS) {
                        return $signals;
                    }

                    $a = $list[$i];
                    $b = $list[$j];
                    $score = $this->similarityScore($a, $b);
                    if ($score >= 0.5) {
                        $signals[] = ['id_a' => $a->id, 'id_b' => $b->id, 'score' => round($score, 4)];
                    }
                }
            }
        }

        return $signals;
    }

    protected function similarityBucketKey(NormalizedProduct $product): ?string
    {
        $reference = $this->normalizeKeyPart($product->supplier_reference);
        $brand = $this->normalizeKeyPart($product->brand);
        $nameSignature = $this->nameSignature($product->name ?? '');

        if ($reference !== '') {
            return 'ref:' . $reference;
        }

        if ($brand !== '' && $nameSignature !== '') {
            return 'brand-name:' . $brand . '|' . $nameSignature;
        }

        if ($nameSignature !== '') {
            return 'name:' . $nameSignature;
        }

        return null;
    }

    protected function normalizeKeyPart(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? '';

        return $value;
    }

    protected function nameSignature(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $tokens = array_values(array_filter(explode(' ', preg_replace('/\s+/u', ' ', $normalized) ?? '')));
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => mb_strlen($token) >= 4));

        return implode(' ', array_slice($tokens, 0, 3));
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
