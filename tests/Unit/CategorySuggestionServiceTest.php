<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\NormalizedProduct;
use App\Services\Categories\CategoryPathBuilderService;
use App\Services\Normalization\CategorySuggestionService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CategorySuggestionServiceTest extends TestCase
{
    public function test_it_matches_specific_categories_from_product_text_even_without_useful_route(): void
    {
        $service = new class(new CategoryPathBuilderService()) extends CategorySuggestionService
        {
            public function categoryEntries(Collection $categories): array
            {
                return $this->buildCategoryEntries($categories);
            }

            public function searchData(NormalizedProduct $product): array
            {
                return $this->buildSearchData($product);
            }

            public function matches(array $entries, array $search): array
            {
                return $this->matchCategories($entries, $search);
            }
        };

        $root = $this->category(1, 'INICIO');
        $pianos = $this->category(2, 'PIANOS', $root);
        $digitales = $this->category(3, 'DIGITALES', $pianos);
        $viento = $this->category(4, 'VIENTO MADERA', $root);
        $flautas = $this->category(5, 'FLAUTAS', $viento);
        $flautaDulce = $this->category(6, 'FLAUTA DULCE', $flautas);

        $entries = $service->categoryEntries(new Collection([
            $pianos,
            $digitales,
            $viento,
            $flautas,
            $flautaDulce,
        ]));

        $digitalPiano = new NormalizedProduct([
            'name' => 'GEWA Piano Digital',
            'tags' => 'GEWA,Piano,Digital',
        ]);

        $scores = $service->matches($entries, $service->searchData($digitalPiano));
        $this->assertSame(3, array_key_first($scores));
        $this->assertGreaterThanOrEqual(15.0, $scores[3]['score']);

        $recorder = new NormalizedProduct([
            'name' => 'Flauta Hohner 9508 Plastico Digitacion Alemana 1 Pieza',
            'category_path_export' => '6142',
            'tags' => '6142,Flauta,Hohner,9508,Plastico,Digitacion,Alemana,Pieza',
        ]);

        $scores = $service->matches($entries, $service->searchData($recorder));
        $this->assertArrayHasKey(6, $scores);
        $this->assertGreaterThanOrEqual(15.0, $scores[6]['score']);
        $this->assertContains(array_key_first($scores), [5, 6]);
    }

    protected function category(int $id, string $name, ?Category $parent = null): Category
    {
        $category = new Category([
            'name' => $name,
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
        $category->id = $id;

        if ($parent) {
            $category->setRelation('parent', $parent);
        }

        return $category;
    }
}
