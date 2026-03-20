<?php

namespace Tests\Unit;

use App\Support\CategoryPathFormatter;
use PHPUnit\Framework\TestCase;

class CategoryPathFormatterTest extends TestCase
{
    public function test_it_formats_routes_with_simple_commas(): void
    {
        $this->assertSame(
            'PIANOS VERTICALES COLA, INICIO, PIANOS, PIANOS ACUSTICOS, PIANOS ESTILO',
            CategoryPathFormatter::formatForDisplay('PIANOS VERTICALES COLA > INICIO / PIANOS & PIANOS ACUSTICOS; PIANOS ESTILO')
        );
    }

    public function test_it_rejects_routes_that_look_like_product_titles(): void
    {
        $this->assertNull(CategoryPathFormatter::normalizeForStorage(
            'Ukelele de concierto Manoa Bambus',
            'Ukelele de concierto Manoa Bambus',
            'Ukelele de concierto Manoa Bambus'
        ));
    }
}
