<?php

namespace App\Support;

class CategoryPathFormatter
{
    public static function normalizeForStorage(?string $rawPath, ?string $name = null, ?string $summary = null): ?string
    {
        $rawPath = trim((string) $rawPath);
        if ($rawPath === '') {
            return null;
        }

        $parts = collect(preg_split('/\s*(?:,|>|›|→|\/|\||;|&)+\s*/u', $rawPath) ?: [])
            ->map(fn ($part) => self::sanitizePart($part))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            return null;
        }

        $dedupedParts = [];
        $seen = [];
        foreach ($parts as $part) {
            $key = self::normalizeForCompare($part);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $dedupedParts[] = $part;
        }

        if ($dedupedParts === []) {
            return null;
        }

        $candidate = implode(', ', $dedupedParts);

        return self::looksLikeProductTitle($candidate, $name, $summary) ? null : $candidate;
    }

    public static function formatForDisplay(?string $path): string
    {
        return self::normalizeForStorage($path) ?? '';
    }

    /**
     * @return array<int, string>
     */
    public static function split(?string $path): array
    {
        $normalized = self::formatForDisplay($path);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($part) => trim((string) $part),
            preg_split('/\s*,\s*/u', $normalized) ?: []
        )));
    }

    protected static function sanitizePart(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    protected static function looksLikeProductTitle(string $candidate, ?string $name, ?string $summary): bool
    {
        $candidateKey = self::normalizeForCompare($candidate);
        $nameKey = self::normalizeForCompare((string) $name);
        $summaryKey = self::normalizeForCompare((string) $summary);

        if ($candidateKey === '') {
            return false;
        }

        if (($nameKey !== '' && ($candidateKey === $nameKey || str_contains($nameKey, $candidateKey) || str_contains($candidateKey, $nameKey)))
            || ($summaryKey !== '' && ($candidateKey === $summaryKey || str_contains($summaryKey, $candidateKey) || str_contains($candidateKey, $summaryKey)))) {
            return true;
        }

        $segments = preg_split('/\s*,\s*/', $candidate) ?: [];
        if (count(array_filter($segments)) <= 1) {
            $tokenCount = count(preg_split('/\s+/u', trim($candidate)) ?: []);
            if ($tokenCount >= 4) {
                return true;
            }
        }

        return false;
    }

    protected static function normalizeForCompare(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;

        return trim($value);
    }
}
