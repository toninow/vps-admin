<?php

namespace App\Services\Normalization;

use App\Models\Category;
use App\Models\NormalizedProduct;
use App\Models\ProductCategorySuggestion;
use App\Services\Categories\CategoryPathBuilderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CategorySuggestionService
{
    protected const STOPWORDS = [
        'de', 'del', 'la', 'las', 'el', 'los', 'y', 'en', 'con', 'para', 'por', 'a', 'al', 'un', 'una',
        'the', 'and', 'for', 'with', 'from', 'set', 'kit', 'new', 'nuevo', 'nuevos', 'nueva', 'nuevas',
        'used', 'usado', 'usados', 'usada', 'usadas',
    ];

    protected const TOKEN_GROUPS = [
        ['guitarra', 'guitarras', 'guitar', 'guitars'],
        ['bajo', 'bajos', 'bass', 'basses'],
        ['ukelele', 'ukeleles', 'ukulele', 'ukuleles'],
        ['mandolina', 'mandolinas', 'mandolin', 'mandolins'],
        ['banjo', 'banjos'],
        ['bateria', 'baterias', 'drum', 'drums', 'bombo', 'bombos', 'tom', 'toms', 'kick'],
        ['percusion', 'percussion'],
        ['piano', 'pianos'],
        ['digital', 'digitales'],
        ['teclado', 'teclados', 'keyboard', 'keyboards', 'synthesizer'],
        ['microfono', 'microfonos', 'microfonia', 'microphone', 'microphones'],
        ['auricular', 'auriculares', 'headphone', 'headphones'],
        ['cable', 'cables'],
        ['cuerda', 'cuerdas', 'string', 'strings'],
        ['violin', 'violines', 'violins'],
        ['violonchelo', 'violonchelos', 'cello', 'cellos'],
        ['saxofon', 'saxofones', 'saxophone', 'saxophones'],
        ['trompeta', 'trompetas', 'trumpet', 'trumpets'],
        ['clarinete', 'clarinetes', 'clarinet', 'clarinets'],
        ['flauta', 'flautas', 'flute', 'flutes'],
        ['dulce', 'recorder'],
        ['tuba', 'tubas', 'viento', 'metal', 'brass'],
        ['acustica', 'acustico', 'acusticas', 'acusticos', 'acoustic'],
        ['electrica', 'electrico', 'electricas', 'electricos', 'electric'],
        ['clasica', 'clasicas', 'classical', 'nylon', 'carbon'],
        ['amplificador', 'amplificadores', 'amplifier', 'amplifiers'],
        ['altavoz', 'altavoces', 'speaker', 'speakers'],
        ['caja', 'cajas', 'altavoz', 'altavoces', 'amplificada', 'amplificado', 'conico', 'dsp', 'sonido', 'audio'],
        ['atril', 'atriles', 'soporte', 'soportes', 'stand', 'stands'],
        ['correa', 'correas', 'cordon', 'cordones', 'strap', 'straps'],
        ['funda', 'fundas', 'estuche', 'estuches', 'bag', 'bags', 'case', 'cases', 'cordura', 'protectora'],
        ['afinador', 'afinadores', 'tuner', 'tuners'],
        ['metronomo', 'metronomos', 'metronome', 'metronomes'],
        ['armonica', 'armonicas', 'harmonica', 'harmonicas'],
        ['audio', 'sonido', 'sound'],
        ['accesorio', 'accesorios', 'accessory', 'accessories'],
        ['resina', 'resinas'],
        ['cana', 'canas', 'reed', 'reeds'],
        ['pedal', 'pedales'],
        ['parche', 'parches', 'head', 'heads', 'drumhead', 'drumheads'],
        ['orff', 'xilofono', 'xilofonos', 'metalofono', 'metalofonos', 'percusion', 'escolar'],
        ['bongo', 'bongos', 'percusion'],
        ['abrazadera', 'abrazaderas', 'conector', 'conectores', 'herraje', 'herrajes', 'accesorio', 'percusion'],
    ];

    protected const GENERIC_ROUTE_SEGMENTS = [
        'accesorios',
        'novedades',
        'pm open products',
        'synthesizer',
        'drum heads',
        'accessories percussion',
        'orquesta y banda',
        'studio computer recording',
        'proamc02',
    ];

    protected const CONTEXT_TOKENS = [
        'accesorio',
        'amplificador',
        'cable',
        'correa',
        'soporte',
        'pedal',
        'microfono',
        'funda',
        'parche',
        'estuche',
    ];

    protected const STRING_FAMILY_TOKENS = [
        'guitarra',
        'bajo',
        'ukelele',
        'mandolina',
        'banjo',
        'violin',
        'violonchelo',
        'cuerda',
    ];

    protected const WIND_FAMILY_TOKENS = [
        'saxofon',
        'clarinete',
        'flauta',
        'dulce',
        'trompeta',
        'tuba',
        'viento',
        'metal',
    ];

    protected const AMBIGUOUS_REGISTER_TOKENS = [
        'soprano',
        'alto',
        'tenor',
        'baritono',
        'baritone',
        'contralto',
    ];

    protected static ?array $tokenCanonicalMap = null;

    public function __construct(
        protected CategoryPathBuilderService $pathBuilder,
    ) {}

    /**
     * Genera sugerencias de categoría por matching textual (sin IA).
     */
    public function suggestForProducts(array $normalizedProductIds): array
    {
        $normalizedProductIds = array_values(array_unique(array_map('intval', $normalizedProductIds)));

        if ($normalizedProductIds === []) {
            return [
                'suggestions_created' => 0,
                'auto_assigned' => 0,
                'total_products' => 0,
            ];
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->get()
            ->keyBy('id');
        $categoryEntries = $this->buildCategoryEntries($categories);
        $created = 0;
        $products = NormalizedProduct::whereIn('id', $normalizedProductIds)->get();

        ProductCategorySuggestion::query()
            ->where('source', 'auto')
            ->whereIn('normalized_product_id', $normalizedProductIds)
            ->delete();

        foreach ($products as $product) {
            $search = $this->buildSearchData($product);
            if (
                $search['route_segments'] === []
                && $search['route_tokens'] === []
                && $search['product_tokens'] === []
            ) {
                continue;
            }

            $suggestions = $this->matchCategories($categoryEntries, $search);
            if (empty($suggestions)) {
                continue;
            }

            foreach ($suggestions as $categoryId => $data) {
                ProductCategorySuggestion::updateOrCreate(
                    [
                        'normalized_product_id' => $product->id,
                        'category_id' => $categoryId,
                    ],
                    [
                        'master_product_id' => $product->master_product_id,
                        'source' => 'auto',
                        'score' => $data['score'],
                    ]
                );
                $created++;
            }
        }

        return [
            'suggestions_created' => $created,
            'auto_assigned' => 0,
            'total_products' => $products->count(),
        ];
    }

    protected function buildSearchData(NormalizedProduct $product): array
    {
        $routeSegments = collect(
            preg_split('/\s*(?:,|>|›|→|\/|\|)\s*/u', (string) ($product->category_path_export ?? '')) ?: []
        )
            ->map(fn ($segment) => $this->normalizePhrase((string) $segment))
            ->filter(fn (string $segment) => $this->isUsableRouteSegment($segment))
            ->filter()
            ->values()
            ->all();

        if (in_array('inicio', $routeSegments, true)) {
            $routeSegments = [];
        }

        $textParts = array_filter([
            $product->name,
            $product->summary,
            $product->description,
            $product->tags,
        ]);

        $productText = $this->normalizePhrase(implode(' ', $textParts));

        return [
            'route_segments' => $routeSegments,
            'route_tokens' => $this->expandAliases($this->tokenize(implode(' ', $routeSegments))),
            'product_tokens' => $this->expandAliases($this->tokenize($productText)),
            'product_text' => $productText,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $categories
     * @return array<int, array{
     *   category: Category,
     *   path_segments: array<int, string>,
     *   own_segment: string,
     *   own_tokens: array<int, string>,
     *   path_tokens: array<int, string>,
     *   depth: int
     * }>
     */
    protected function buildCategoryEntries(Collection $categories): array
    {
        $entries = [];

        foreach ($categories as $cat) {
            $segments = [];
            $current = $cat;

            while ($current) {
                $segments[] = $this->normalizePhrase((string) $current->name);
                $current->loadMissing('parent');
                $current = $current->parent;
            }

            $segments = array_values(array_filter(array_reverse($segments)));
            $ownSegment = end($segments) ?: '';

            $entries[$cat->id] = [
                'category' => $cat,
                'path_segments' => $segments,
                'own_segment' => $ownSegment,
                'own_tokens' => $this->expandAliases($this->tokenize($ownSegment)),
                'path_tokens' => $this->expandAliases($this->tokenize(implode(' ', $segments))),
                'depth' => count($segments),
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, array{
     *   category: Category,
     *   path_segments: array<int, string>,
     *   own_segment: string,
     *   own_tokens: array<int, string>,
     *   path_tokens: array<int, string>,
     *   depth: int
     * }>  $categoryEntries
     * @param  array{route_segments: array<int, string>, route_tokens: array<int, string>, product_tokens: array<int, string>, product_text: string}  $search
     * @return array<int, array{score: float, route_segment_matches: int, matched_tokens: int}>
     */
    protected function matchCategories(array $categoryEntries, array $search): array
    {
        $scores = [];

        foreach ($categoryEntries as $categoryId => $entry) {
            if ($this->isConflictingFamilyMatch($entry, $search)) {
                continue;
            }

            $routeSegmentMatches = count(array_intersect($search['route_segments'], $entry['path_segments']));
            $ownRouteMatches = count(array_intersect($search['route_tokens'], $entry['own_tokens']));
            $ownProductMatches = count(array_intersect($search['product_tokens'], $entry['own_tokens']));
            $pathRouteMatches = count(array_intersect($search['route_tokens'], $entry['path_tokens']));
            $pathProductMatches = count(array_intersect($search['product_tokens'], $entry['path_tokens']));
            $phraseMatches = 0;

            if ($entry['own_segment'] !== '') {
                if (in_array($entry['own_segment'], $search['route_segments'], true)) {
                    $phraseMatches += 2;
                }
                if ($search['product_text'] !== '' && str_contains($search['product_text'], $entry['own_segment'])) {
                    $phraseMatches += 1;
                }
            }

            $matchedTokens = $ownRouteMatches + $ownProductMatches + $pathRouteMatches + $pathProductMatches;
            $ownTokenCount = max(1, count($entry['own_tokens']));
            $ownCoverageBonus = (($ownRouteMatches + $ownProductMatches) / $ownTokenCount) * 12.0;
            $unmatchedOwnPenalty = max(0, $ownTokenCount - ($ownRouteMatches + $ownProductMatches)) * 2.0;
            $contextPenalty = count(array_diff(
                array_intersect($entry['own_tokens'], self::CONTEXT_TOKENS),
                array_unique(array_merge($search['route_tokens'], $search['product_tokens']))
            )) * 12.0;

            $score = ($routeSegmentMatches * 12.0)
                + ($ownRouteMatches * 9.0)
                + ($ownProductMatches * 10.0)
                + ($pathRouteMatches * 5.0)
                + ($pathProductMatches * 4.0)
                + ($phraseMatches * 10.0)
                + ($entry['depth'] * 0.75)
                + $ownCoverageBonus
                - $unmatchedOwnPenalty
                - $contextPenalty;

            if ($score <= 0.0) {
                continue;
            }

            if ($routeSegmentMatches === 0 && $ownRouteMatches === 0 && $ownProductMatches === 0 && $phraseMatches === 0) {
                continue;
            }

            $scores[$categoryId] = [
                'score' => round($score, 4),
                'route_segment_matches' => $routeSegmentMatches,
                'matched_tokens' => $matchedTokens,
            ];
        }

        uasort($scores, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scores, 0, 5, true);
    }

    /**
     * @param  array{
     *   category: Category,
     *   path_segments: array<int, string>,
     *   own_segment: string,
     *   own_tokens: array<int, string>,
     *   path_tokens: array<int, string>,
     *   depth: int
     * } $entry
     * @param  array{route_segments: array<int, string>, route_tokens: array<int, string>, product_tokens: array<int, string>, product_text: string}  $search
     */
    protected function isConflictingFamilyMatch(array $entry, array $search): bool
    {
        $searchTokens = array_values(array_unique(array_merge($search['route_tokens'], $search['product_tokens'])));
        $categoryTokens = array_values(array_unique(array_merge($entry['own_tokens'], $entry['path_tokens'])));

        $searchHasString = $this->hasAnyToken($searchTokens, self::STRING_FAMILY_TOKENS);
        $searchHasWind = $this->hasAnyToken($searchTokens, self::WIND_FAMILY_TOKENS);
        $categoryHasString = $this->hasAnyToken($categoryTokens, self::STRING_FAMILY_TOKENS);
        $categoryHasWind = $this->hasAnyToken($categoryTokens, self::WIND_FAMILY_TOKENS);
        $categoryHasAmbiguousRegister = $this->hasAnyToken($entry['own_tokens'], self::AMBIGUOUS_REGISTER_TOKENS)
            || $this->hasAnyToken($categoryTokens, self::AMBIGUOUS_REGISTER_TOKENS);

        if ($searchHasString && ! $searchHasWind && $categoryHasWind && ! $categoryHasString) {
            return true;
        }

        if ($searchHasWind && ! $searchHasString && $categoryHasString && ! $categoryHasWind) {
            return true;
        }

        if (
            $categoryHasAmbiguousRegister
            && $categoryHasWind
            && $searchHasString
            && ! $searchHasWind
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    protected function expandAliases(array $tokens): array
    {
        $expanded = [];
        $canonicalMap = $this->tokenCanonicalMap();

        foreach ($tokens as $token) {
            $expanded[] = $canonicalMap[$token] ?? $token;
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    protected function normalizePhrase(string $text): string
    {
        $text = Str::ascii(mb_strtolower($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }

    /**
     * @return array<int, string>
     */
    protected function tokenize(string $text): array
    {
        $tokens = array_filter(explode(' ', $this->normalizePhrase($text)));
        $clean = [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 4) {
                continue;
            }
            if (in_array($token, self::STOPWORDS, true)) {
                continue;
            }
            $clean[] = $token;
        }

        return array_values(array_unique($clean));
    }

    protected function isUsableRouteSegment(string $segment): bool
    {
        if ($segment === '') {
            return false;
        }

        if (! preg_match('/[a-z]/', $segment)) {
            return false;
        }

        if (in_array($segment, self::GENERIC_ROUTE_SEGMENTS, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $haystack
     * @param  array<int, string>  $needles
     */
    protected function hasAnyToken(array $haystack, array $needles): bool
    {
        return array_intersect($haystack, $needles) !== [];
    }

    /**
     * @return array<string, string>
     */
    protected function tokenCanonicalMap(): array
    {
        if (self::$tokenCanonicalMap !== null) {
            return self::$tokenCanonicalMap;
        }

        $map = [];

        foreach (self::TOKEN_GROUPS as $group) {
            $normalizedGroup = array_values(array_unique(array_filter(array_map(
                fn (string $token): string => $this->normalizePhrase($token),
                $group
            ))));

            $canonical = $normalizedGroup[0] ?? null;
            if ($canonical === null) {
                continue;
            }

            foreach ($normalizedGroup as $token) {
                $map[$token] = $canonical;
            }
        }

        self::$tokenCanonicalMap = $map;

        return self::$tokenCanonicalMap;
    }
}
