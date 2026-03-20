<?php

namespace App\Services\Normalization;

class ProductTextFormatterService
{
    protected const TAG_STOPWORDS = [
        'de', 'del', 'la', 'las', 'el', 'los', 'y', 'en', 'con', 'para', 'por', 'a', 'al', 'un', 'una',
        'the', 'and', 'for', 'with', 'from', 'set', 'kit', 'new', 'nuevo', 'nueva', 'nuevos', 'nuevas',
        'color', 'finish', 'natural', 'black', 'white', 'sunburst',
    ];

    protected const NAME_LOWERCASE_WORDS = [
        'de', 'del', 'la', 'las', 'el', 'los', 'y', 'en', 'con', 'para', 'por', 'a', 'al',
        'the', 'and', 'for', 'with', 'of', 'from', 'to', 'in', 'on',
    ];

    public function buildNormalizedName(
        ?string $name,
        ?string $brand = null,
        ?string $supplierReference = null,
        ?string $summary = null,
        ?string $supplierSlug = null,
        ?array $rawData = null
    ): string {
        $name = $this->resolvePreferredNameCandidate($supplierSlug, $rawData, $name, $summary, $brand);
        $originalBaseName = $this->cleanProductNameText($name);
        $brand = $this->cleanBrandText($brand, $originalBaseName);
        $summary = $this->cleanProductNameText($summary);
        $supplierReference = $this->cleanInlineText($supplierReference);
        $baseName = $this->stripSupplierReferenceArtifact($originalBaseName, $supplierReference);
        $baseName = $this->stripLeadingArtifacts($baseName);

        if ($baseName === '' || ($this->looksLikeBareCode($baseName) && $summary !== '' && ! $this->looksLikeBareCode($summary))) {
            $baseName = $summary !== '' ? $summary : $supplierReference;
        }

        if ($this->shouldPreferSourceNameOnly($supplierSlug)) {
            $preferred = $baseName;

            if ($summary !== '' && ($preferred === '' || $this->looksLikeCategoryLabel($preferred))) {
                $preferred = $summary;
            }

            return $this->normalizeProductNameCasing($preferred, $brand);
        }

        $baseName = $this->stripSegmentArtifact($baseName, $brand);

        $segments = [];

        if ($brand !== '') {
            $segments[] = $brand;
        }

        $model = $this->extractModelCandidate($supplierReference, $baseName, $brand, $originalBaseName, $supplierSlug);
        if ($model !== '') {
            $segments[] = $model;
            $baseName = $this->stripSegmentArtifact($baseName, $model);
        }

        if ($baseName !== '') {
            $segments[] = $baseName;
        }

        $segments = $this->dedupeSegments($segments);

        return $this->normalizeProductNameCasing(implode(' ', $segments), $brand);
    }

    public function buildSummary(?string $summary, string $fallbackName): string
    {
        $summary = $this->cleanInlineText($summary, 800);
        $fallbackName = $this->cleanInlineText($fallbackName, 800);

        if ($summary === '' || $this->looksLikeBareCode($summary)) {
            return $fallbackName;
        }

        return $summary;
    }

    public function cleanSummaryForDisplay(?string $summary, ?string $supplierReference = null, string $fallbackName = ''): string
    {
        $summary = $this->cleanInlineText($summary, 800);
        $summary = $this->stripSupplierReferenceArtifact($summary, $this->cleanInlineText($supplierReference));

        return $this->buildSummary($summary, $fallbackName);
    }

    public function buildTags(
        ?string $name,
        ?string $brand = null,
        ?string $supplierReference = null,
        ?string $summary = null,
        ?string $categoryPath = null,
        ?string $description = null
    ): string {
        $candidates = [];

        $brand = $this->cleanInlineText($brand, 80);
        if ($brand !== '') {
            $candidates[] = $brand;
        }

        $supplierReference = $this->cleanInlineText($supplierReference, 80);
        if ($supplierReference !== '' && ! ctype_digit(str_replace(['-', '_', '/', '.'], '', $supplierReference))) {
            $candidates[] = $supplierReference;
        }

        foreach ($this->extractMeaningfulPhrases($categoryPath, 4) as $phrase) {
            $candidates[] = $phrase;
        }

        foreach ($this->extractMeaningfulTokens($name, 8) as $token) {
            $candidates[] = $token;
        }

        foreach ($this->extractMeaningfulTokens($summary, 8) as $token) {
            $candidates[] = $token;
        }

        foreach ($this->extractMeaningfulTokens($description, 5) as $token) {
            $candidates[] = $token;
        }

        $deduped = $this->dedupeSegments($candidates);

        return implode(',', array_slice($deduped, 0, 12));
    }

    public function cleanInlineText(?string $value, ?int $maxLength = null): string
    {
        $value = $this->repairMojibake((string) $value);
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = $this->stripMojibakeArtifacts($value);
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);
        $value = $this->stripWrappingQuotes($value);

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $value = rtrim(mb_substr($value, 0, $maxLength));
        }

        return $value;
    }

    public function cleanProductNameText(?string $value, ?int $maxLength = 255): string
    {
        $value = $this->cleanInlineText($value, null);

        if ($value === '' || $value === '0') {
            return '';
        }

        $value = $this->extractSpreadsheetFallback($value) ?: $value;
        $value = preg_replace('/^\[\s*".*?"\s*\]\s*[-:|\/]\s*/u', '', $value) ?? $value;
        $value = preg_replace('/^\[\s*.*?\s*\]\s*[-:|\/]\s*/u', '', $value) ?? $value;
        $value = preg_replace('/\s+([®™©])/u', '$1', $value) ?? $value;
        $value = preg_replace('/([®™©])\s*/u', '$1 ', $value) ?? $value;
        $value = preg_replace('/\s{2,}/u', ' ', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B-");

        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $value = rtrim(mb_substr($value, 0, $maxLength));
        }

        return $value;
    }

    public function normalizeProductNameCasing(?string $value, ?string $brand = null): string
    {
        $value = $this->cleanProductNameText($value, 255);
        $brand = $this->cleanBrandText($brand);

        if ($value === '') {
            return '';
        }

        $brandMap = [];
        foreach (preg_split('/\s+/u', $brand) ?: [] as $brandToken) {
            $cleanBrandToken = trim((string) $brandToken);
            if ($cleanBrandToken === '') {
                continue;
            }

            $brandMap[$this->normalizeForCompare($cleanBrandToken)] = $cleanBrandToken;
        }

        $parts = preg_split('/(\s+)/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$value];
        $wordIndex = 0;

        foreach ($parts as $index => $part) {
            if ($part === '' || preg_match('/^\s+$/u', $part)) {
                continue;
            }

            $parts[$index] = $this->normalizeNameChunk($part, $wordIndex === 0, $brandMap);
            $wordIndex++;
        }

        return $this->cleanProductNameText(implode('', $parts), 255);
    }

    public function formatCharacteristics(?string $description, ?string $fallbackName = null): string
    {
        $description = $this->cleanInlineText($description, 4000);
        $fallbackName = $this->cleanInlineText($fallbackName, 1000);
        $description = preg_replace('/\bcaracter[ií]sticas:\s*/iu', "\n", $description) ?? $description;
        $description = preg_replace('/\b(elementos incluidos|incluye|contenido del paquete|contenido del pack|dimensiones)\s*:\s*/iu', "\n$1: ", $description) ?? $description;
        $description = trim((string) $description);

        if ($description === '') {
            $description = $fallbackName;
        }

        if ($description === '') {
            return 'Características:';
        }

        $items = collect(preg_split('/\s*(?:•|;|\r\n|\r|\n)+\s*/u', $description) ?: [])
            ->flatMap(function ($item) {
                return $this->explodeCharacteristicItem((string) $item);
            })
            ->map(fn ($item) => $this->cleanInlineText((string) $item, 1000))
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            $items = collect([$fallbackName !== '' ? $fallbackName : $description]);
        } elseif ($items->count() === 1) {
            $single = (string) $items->first();
            if ($single === '') {
                $single = $fallbackName !== '' ? $fallbackName : $description;
            }

            $items = collect([$single]);
        }

        return "Características:\n" . $items
            ->map(fn (string $item) => '• ' . $item)
            ->implode("\n");
    }

    /**
     * @return array<int, string>
     */
    protected function explodeCharacteristicItem(string $item): array
    {
        $item = $this->cleanInlineText($item, 1500);
        if ($item === '') {
            return [];
        }

        if (preg_match('/^(elementos incluidos|incluye|contenido del paquete|contenido del pack|dimensiones)\s*:\s*(.+)$/iu', $item, $matches)) {
            $label = ucfirst(mb_strtolower($matches[1], 'UTF-8'));
            $payload = $this->cleanInlineText($matches[2], 1200);

            return $payload !== '' ? ["{$label}: {$payload}"] : [$label];
        }

        $commaSeparated = preg_split('/\s*,\s*/u', $item) ?: [];
        $commaSeparated = array_values(array_filter(array_map(
            fn ($part) => $this->cleanInlineText((string) $part, 300),
            $commaSeparated
        )));

        if (count($commaSeparated) >= 4) {
            return $commaSeparated;
        }

        return [$item];
    }

    protected function stripWrappingQuotes(string $value): string
    {
        $trimmed = trim($value);

        while ($trimmed !== '' && preg_match('/^(["\']{1,3})(.*)(["\']{1,3})$/us', $trimmed, $matches)) {
            if ($matches[1][0] !== $matches[3][strlen($matches[3]) - 1]) {
                break;
            }

            $inner = trim((string) $matches[2]);
            if ($inner === '' || $inner === $trimmed) {
                break;
            }

            $trimmed = $inner;
        }

        return trim($trimmed, " \t\n\r\0\x0B'\"");
    }

    protected function extractModelCandidate(
        string $supplierReference,
        string $baseName,
        string $brand,
        string $originalBaseName = '',
        ?string $supplierSlug = null
    ): string
    {
        if ($supplierReference === '') {
            return '';
        }

        if ($supplierSlug === 'gewa') {
            return '';
        }

        if (preg_match('/^\d+$/', $supplierReference)) {
            return '';
        }

        if (! preg_match('/[A-Za-z]/u', $supplierReference)) {
            return '';
        }

        if (! preg_match('/[\d\-\/_.]/u', $supplierReference)) {
            return '';
        }

        if (preg_match('/^[A-Z]{4,}\d/i', $supplierReference)) {
            return '';
        }

        if (preg_match('/^\d{5,}[A-Z]?$/i', $supplierReference)) {
            return '';
        }

        if ($this->startsWithReferenceArtifact($originalBaseName, $supplierReference)) {
            return '';
        }

        if (mb_strlen($supplierReference) > 12) {
            return '';
        }

        if ($this->containsNormalizedSegment($baseName, $supplierReference)) {
            return '';
        }

        if ($brand !== '' && $this->containsNormalizedSegment($brand, $supplierReference)) {
            return '';
        }

        return $supplierReference;
    }

    protected function resolvePreferredNameCandidate(
        ?string $supplierSlug,
        ?array $rawData,
        ?string $name,
        ?string $summary,
        ?string $brand
    ): string {
        $supplierSlug = trim((string) $supplierSlug);
        $rawData = is_array($rawData) ? $rawData : [];

        $providerCandidate = match ($supplierSlug) {
            'honsuy' => $this->firstMeaningfulRawValue($rawData, ['Nombre', 'Name']),
            'alhambra' => $this->firstMeaningfulRawValue($rawData, ['Title']),
            'daddario' => $this->firstMeaningfulRawValue($rawData, ['Nombre del Producto (Web)', 'Nombre del Producto']),
            'madridmusical' => $this->firstMeaningfulRawValue($rawData, ['Nombre']),
            'vallestrade' => $this->firstMeaningfulRawValue($rawData, ['Nombre artículo', 'Meta título']),
            'zentralmedia' => $this->firstMeaningfulRawValue($rawData, ['Descripcion articulo', 'Descripcion', 'Descripcion2 articulo']),
            'gewa' => $this->buildGewaNameCandidate($rawData, $brand),
            default => '',
        };

        if ($providerCandidate !== '') {
            return $providerCandidate;
        }

        $name = $this->cleanProductNameText($name);
        if ($name !== '' && ! $this->looksLikeSpreadsheetFormula($name)) {
            return $name;
        }

        foreach (['Nombre', 'Title', 'Nombre del Producto (Web)', 'Nombre del Producto', 'Descripcion', 'Descripcion articulo'] as $key) {
            $candidate = $this->firstMeaningfulRawValue($rawData, [$key]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $summary = $this->cleanProductNameText($summary);

        return $summary;
    }

    protected function buildGewaNameCandidate(array $rawData, ?string $brand): string
    {
        $family = $this->cleanProductNameText($rawData['ccgbel1'] ?? '');
        $variant = $this->cleanProductNameText($rawData['ccgbel2'] ?? '');
        $group = $this->cleanProductNameText($rawData['ccgbel3'] ?? '');
        $brand = $this->cleanBrandText($brand ?? ($rawData['marke'] ?? ''), $group !== '' ? $group : $family);

        if ($family === '' && $group === '') {
            return '';
        }

        if ($family !== '' && $this->looksLikeConcreteGewaProductName($family)) {
            $segments = [];
            if ($brand !== '' && ! $this->containsNormalizedSegment($family, $brand)) {
                $segments[] = $brand;
            }
            if ($this->isGewaTypeQualifier($variant) && ! $this->containsNormalizedSegment($family, $variant)) {
                $segments[] = $variant;
            }
            $segments[] = $family;

            return $this->cleanProductNameText(implode(' ', $this->dedupeSegments($segments)), 255);
        }

        $type = $this->stripSegmentArtifact($group, $brand);
        $type = $this->normalizeGewaGroupLabel($type);
        $model = '';

        if ($family !== '' && preg_match('/([A-Z]{1,4}\s?\d{2,4}[A-Z]?)$/u', $family, $matches)) {
            $model = trim($matches[1]);
            $family = trim((string) preg_replace('/([A-Z]{1,4}\s?\d{2,4}[A-Z]?)$/u', '', $family));
        }

        if ($type === '' || $this->looksLikeBareCode($type)) {
            $type = $family;
        }

        $segments = array_filter([$model, $type]);
        if ($segments === []) {
            return $family;
        }

        return implode(' ', $segments);
    }

    protected function looksLikeConcreteGewaProductName(string $family): bool
    {
        return (bool) preg_match('/\b(?:ukulele|ukelele|guitar|guitarra|violin|viol[ií]n|cello|banjo|mandolin|mandolina|sax|trumpet|trombone|piano)\b/iu', $family);
    }

    protected function isGewaTypeQualifier(string $value): bool
    {
        $value = mb_strtolower($this->cleanProductNameText($value), 'UTF-8');

        return in_array($value, [
            'soprano',
            'concert',
            'concierto',
            'tenor',
            'baritone',
            'baritono',
            'alto',
            'bass',
            'bajo',
        ], true);
    }

    protected function normalizeGewaGroupLabel(string $value): string
    {
        $value = $this->cleanProductNameText($value);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '&')) {
            $parts = array_values(array_filter(array_map(
                fn ($part) => $this->cleanProductNameText($part),
                preg_split('/\s*&\s*/u', $value) ?: []
            )));
            $value = (string) end($parts);
        }

        $value = preg_replace('/^(?:vgs|tennessee|gewa)\s+/iu', '', $value) ?? $value;
        $value = preg_replace('/\bukeleles\b/iu', 'Ukulele', $value) ?? $value;
        $value = preg_replace('/\bpianos digitales\b/iu', 'Piano Digital', $value) ?? $value;

        return $this->cleanProductNameText($value);
    }

    protected function firstMeaningfulRawValue(array $rawData, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $rawData)) {
                continue;
            }

            $candidate = $this->cleanProductNameText((string) $rawData[$key]);
            if ($candidate !== '' && ! $this->looksLikeSpreadsheetFormula($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $brandMap
     */
    protected function normalizeNameChunk(string $chunk, bool $isFirstWord, array $brandMap): string
    {
        if (! preg_match('/^([(\[{“"\'«®]*)(.*?)([)\]}”"\'»®,.:;!?]*)$/u', $chunk, $matches)) {
            return $chunk;
        }

        $leading = $matches[1] ?? '';
        $core = $matches[2] ?? $chunk;
        $trailing = $matches[3] ?? '';

        if ($core === '') {
            return $chunk;
        }

        $core = implode('', array_map(
            fn ($part) => in_array($part, ['-', '/', '.'], true)
                ? $part
                : $this->normalizeNameSubToken($part, $isFirstWord, $brandMap),
            preg_split('/([\-\/.])/u', $core, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$core]
        ));

        return $leading . $core . $trailing;
    }

    /**
     * @param  array<string, string>  $brandMap
     */
    protected function normalizeNameSubToken(string $token, bool $isFirstWord, array $brandMap): string
    {
        if ($token === '' || ! preg_match('/\p{L}/u', $token)) {
            return $token;
        }

        if (str_contains($token, "'") || str_contains($token, '’')) {
            $separator = str_contains($token, '’') ? '’' : "'";

            return implode($separator, array_map(
                fn ($part, $index) => $this->normalizeNameSubToken($part, $isFirstWord && $index === 0, $brandMap),
                explode($separator, $token),
                array_keys(explode($separator, $token))
            ));
        }

        $normalized = $this->normalizeForCompare($token);
        if ($normalized !== '' && isset($brandMap[$normalized])) {
            return $brandMap[$normalized];
        }

        $alphaOnly = preg_replace('/[^\p{L}]+/u', '', $token) ?? $token;
        $lower = mb_strtolower($token, 'UTF-8');

        if (! $isFirstWord && in_array($lower, self::NAME_LOWERCASE_WORDS, true)) {
            return $lower;
        }

        if (preg_match('/\d/u', $token)) {
            return $token;
        }

        if (preg_match('/^[IVXLCDM]+$/u', $token)) {
            return mb_strtoupper($token, 'UTF-8');
        }

        if (mb_strlen($alphaOnly, 'UTF-8') <= 3 && mb_strtoupper($token, 'UTF-8') === $token) {
            return $token;
        }

        if (preg_match('/[a-záéíóúüñ]/u', $token) && preg_match('/[A-ZÁÉÍÓÚÜÑ]/u', $token)) {
            return $token;
        }

        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }

    protected function cleanBrandText(?string $brand, string $baseName = ''): string
    {
        $brand = $this->cleanInlineText($brand, 120);

        if ($brand === '') {
            return '';
        }

        if (preg_match('/^\[\s*".*?"\s*\]$/u', $brand)) {
            $decoded = json_decode($brand, true);
            if (is_array($decoded) && isset($decoded[0])) {
                $brand = $this->cleanInlineText((string) $decoded[0], 120);
            }
        }

        if (str_contains($brand, '|')) {
            $brand = $this->cleanInlineText((string) explode('|', $brand)[0], 120);
        }

        if (preg_match('/^(.+?)\s+made\s+in\b/iu', $brand, $matches)) {
            $brand = $this->cleanInlineText((string) $matches[1], 120);
        }

        if ($baseName !== '') {
            $brandTokens = preg_split('/\s+/u', $brand) ?: [];
            $firstToken = trim((string) ($brandTokens[0] ?? ''));
            if ($firstToken !== '' && $this->containsNormalizedSegment($baseName, $firstToken)) {
                return $firstToken;
            }
        }

        return $brand;
    }

    protected function stripSegmentArtifact(string $text, string $segment): string
    {
        $text = $this->cleanProductNameText($text);
        $segment = $this->cleanProductNameText($segment);

        if ($text === '' || $segment === '') {
            return $text;
        }

        $quoted = preg_quote($segment, '/');
        $patterns = [
            '/^\s*' . $quoted . '\s*[-:|\/]?\s*/iu',
            '/\s*[-:|\/]?\s*' . $quoted . '\s*$/iu',
            '/\b' . $quoted . '\b/iu',
        ];

        foreach ($patterns as $pattern) {
            $candidate = preg_replace($pattern, ' ', $text) ?? $text;
            $candidate = $this->cleanProductNameText($candidate);
            if ($candidate !== '' || strcasecmp($text, $segment) === 0) {
                $text = $candidate;
            }
        }

        return $text;
    }

    protected function stripLeadingArtifacts(string $text): string
    {
        $text = preg_replace('/^\s*(?:0|n\/a|null)\s*[-:|\/]?\s*/iu', '', $text) ?? $text;

        return $this->cleanProductNameText($text);
    }

    protected function shouldPreferSourceNameOnly(?string $supplierSlug): bool
    {
        return in_array((string) $supplierSlug, [
            'alhambra',
            'daddario',
            'earpro',
            'euromusica',
            'fender',
            'honsuy',
            'knobloch',
            'ludwig_nl',
            'madridmusical',
            'ortola',
            'ritmo',
            'samba',
            'tico',
        ], true);
    }

    protected function looksLikeCategoryLabel(string $value): bool
    {
        $value = $this->cleanProductNameText($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/\((?:[^)]*fundas|[^)]*estuches|[^)]*accesorios|[^)]*accessories)/iu', $value)) {
            return true;
        }

        return (bool) preg_match('/\b(?:accessories|accesorios|guitarras serie|series?|fundas|estuches)\b/iu', $value);
    }

    protected function looksLikeSpreadsheetFormula(string $value): bool
    {
        return str_starts_with(trim($value), '=');
    }

    protected function extractSpreadsheetFallback(string $value): string
    {
        if (! $this->looksLikeSpreadsheetFormula($value)) {
            return '';
        }

        if (preg_match_all('/"((?:[^"]|"")*)"/u', $value, $matches) && ! empty($matches[1])) {
            $last = str_replace('""', '"', (string) end($matches[1]));
            return $this->cleanInlineText($last, 255);
        }

        return '';
    }

    protected function repairMojibake(string $value): string
    {
        if (! preg_match('/Ã.|Â.|â[\x80-\xBF]|Ã|Â/u', $value)) {
            return $value;
        }

        $best = $value;
        $bestScore = $this->mojibakeScore($value);

        foreach (['ISO-8859-1', 'Windows-1252', 'CP1252'] as $encoding) {
            $candidate = $value;

            for ($i = 0; $i < 3; $i++) {
                $converted = @mb_convert_encoding($candidate, 'UTF-8', $encoding);
                if (! is_string($converted) || $converted === '') {
                    break;
                }

                $candidate = $converted;
                $score = $this->mojibakeScore($candidate);

                if ($score < $bestScore) {
                    $best = $candidate;
                    $bestScore = $score;
                }

                if ($score === 0) {
                    break;
                }
            }
        }

        return $best;
    }

    protected function mojibakeScore(string $value): int
    {
        preg_match_all('/(?:Ã.|Â.|â[\x80-\xBF]|Ã|Â)/u', $value, $matches);

        return count($matches[0] ?? []);
    }

    protected function stripMojibakeArtifacts(string $value): string
    {
        if ($this->mojibakeScore($value) < 2) {
            return $value;
        }

        $cleaned = preg_replace('/(?:Ã.|Â.|â[\x80-\xBF]|Ã|Â){2,}/u', ' ', $value) ?? $value;
        $cleaned = preg_replace('/(?:Ã.|Â.|â[\x80-\xBF]|Ã|Â)/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }

    protected function stripSupplierReferenceArtifact(string $text, string $supplierReference): string
    {
        if ($text === '' || $supplierReference === '') {
            return $text;
        }

        $quotedReference = preg_quote($supplierReference, '/');
        $patterns = [
            '/^\s*' . $quotedReference . '\s*[-:|\/]\s*/iu',
            '/\s*[-:|\/]\s*' . $quotedReference . '\s*$/iu',
            '/(?<![\p{L}\p{N}])' . $quotedReference . '(?![\p{L}\p{N}])/iu',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $text = preg_replace('/\s*[-:|\/]\s*[-:|\/]\s*/u', ' - ', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', $text) ?? $text;

        return $this->cleanInlineText($text, 255);
    }

    protected function startsWithReferenceArtifact(string $text, string $supplierReference): bool
    {
        if ($text === '' || $supplierReference === '') {
            return false;
        }

        return (bool) preg_match('/^\s*' . preg_quote($supplierReference, '/') . '\s*[-:|\/]\s*/iu', $text);
    }

    /**
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    protected function dedupeSegments(array $segments): array
    {
        $deduped = [];
        $seen = [];

        foreach ($segments as $segment) {
            $segment = $this->cleanInlineText($segment);
            if ($segment === '') {
                continue;
            }

            $normalized = $this->normalizeForCompare($segment);
            if ($normalized === '') {
                continue;
            }

            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $deduped[] = $segment;
        }

        return $deduped;
    }

    /**
     * @return array<int, string>
     */
    protected function extractMeaningfulTokens(?string $text, int $limit = 6): array
    {
        $text = $this->cleanInlineText($text, 1200);
        if ($text === '') {
            return [];
        }

        $tokens = preg_split('/[\s,;|()\\[\\]{}]+/u', $text) ?: [];
        $out = [];

        foreach ($tokens as $token) {
            $token = trim($token, " \t\n\r\0\x0B-_.:/");
            if ($token === '') {
                continue;
            }

            $lower = mb_strtolower($token, 'UTF-8');
            $normalized = $this->normalizeForCompare($token);

            if ($normalized === '' || mb_strlen($lower, 'UTF-8') < 4) {
                continue;
            }

            if (in_array($lower, self::TAG_STOPWORDS, true)) {
                continue;
            }

            $out[] = $this->cleanInlineText($token, 60);

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    protected function extractMeaningfulPhrases(?string $text, int $limit = 4): array
    {
        $text = $this->cleanInlineText($text, 800);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:,|>|›|→|\\/|\\|)\s*/u', $text) ?: [];
        $out = [];

        foreach ($parts as $part) {
            $part = $this->cleanInlineText($part, 80);
            if ($part === '' || mb_strlen($part, 'UTF-8') < 4) {
                continue;
            }

            $out[] = $part;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    protected function containsNormalizedSegment(string $haystack, string $needle): bool
    {
        $haystack = $this->normalizeForCompare($haystack);
        $needle = $this->normalizeForCompare($needle);

        if ($haystack === '' || $needle === '') {
            return false;
        }

        return str_contains($haystack, $needle);
    }

    protected function looksLikeBareCode(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (mb_strlen($value) > 60) {
            return false;
        }

        if (str_contains($value, ' ')) {
            return false;
        }

        return (bool) preg_match('/^[A-Z0-9._\-\/]+$/u', mb_strtoupper($value, 'UTF-8'));
    }

    protected function normalizeForCompare(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = $this->removeAccents($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;

        return trim($value);
    }

    protected function removeAccents(string $value): string
    {
        return strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);
    }
}
