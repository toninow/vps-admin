<?php

namespace Tests\Unit;

use App\Services\Normalization\AdvancedNormalizationService;
use App\Services\Normalization\ProductTextFormatterService;
use PHPUnit\Framework\TestCase;

class ProductCatalogFormattingTest extends TestCase
{
    public function test_it_parses_european_price_with_dot_as_thousands_separator(): void
    {
        $service = new AdvancedNormalizationService(new ProductTextFormatterService());

        $this->assertSame(1149.0, $service->normalizePrice('1.149'));
        $this->assertSame(949.0, $service->normalizePrice('949'));
        $this->assertSame(5135.0, $service->normalizePrice('5135'));
        $this->assertSame(2970.66, $service->normalizePrice('2970,66'));
    }

    public function test_it_formats_characteristics_as_bulleted_block(): void
    {
        $formatter = new ProductTextFormatterService();

        $formatted = $formatter->formatCharacteristics('Características: Tapa maciza; Fondo de arce; Estuche incluido');

        $this->assertSame("Características:\n• Tapa maciza\n• Fondo de arce\n• Estuche incluido", $formatted);
    }

    public function test_it_builds_product_names_without_artificial_hyphens(): void
    {
        $formatter = new ProductTextFormatterService();

        $this->assertSame(
            'PROEL Cable Linea Jack-Jack 10 M. ESO130LU10 BK',
            $formatter->buildNormalizedName(
                'PROEL - CABLE LINEA JACK-JACK 10 M. ESO130LU10 BK',
                'PROEL',
                '000356',
                'CABLE LINEA JACK-JACK 10 M. ESO130LU10 BK'
            )
        );

        $this->assertSame(
            'AMADEUS ACT302 Corneta en DO',
            $formatter->buildNormalizedName(
                'CORNETA AMADEUS EN DO',
                'AMADEUS',
                'ACT302'
            )
        );
    }

    public function test_it_prefers_supplier_specific_raw_name_candidates(): void
    {
        $formatter = new ProductTextFormatterService();

        $this->assertSame(
            'Muñequera con 3 Cascabeles',
            $formatter->buildNormalizedName(
                '=IFERROR(__xludf.DUMMYFUNCTION("GOOGLETRANSLATE(B2,""es"",""en"")"),"WRISTBAND WITH 3 BELLS")',
                null,
                '47560',
                null,
                'honsuy',
                ['Nombre' => 'MUÑEQUERA CON 3 CASCABELES']
            )
        );

        $this->assertSame(
            'GEWA UP 395 Piano Digital',
            $formatter->buildNormalizedName(
                '0',
                'GEWA Made in Germany',
                '120397E',
                null,
                'gewa',
                [
                    'ccgbel1' => 'Pianos Digitales UP 395',
                    'ccgbel3' => 'GEWA Piano Digital',
                    'marke' => 'GEWA Made in Germany',
                ]
            )
        );

        $this->assertSame(
            'GEWA Soprano Pineapple Ukulele Manoa K-PA-BBH',
            $formatter->buildNormalizedName(
                'GEWA VG512145 & TENNESSEE & VGS Ukeleles',
                'GEWA',
                'VG512145',
                null,
                'gewa',
                [
                    'ccgbel1' => 'Pineapple Ukulele Manoa K-PA-BBH',
                    'ccgbel2' => 'Soprano',
                    'ccgbel3' => 'GEWA & TENNESSEE & VGS Ukeleles',
                    'marke' => 'GEWA',
                ]
            )
        );

        $this->assertSame(
            "D'Addario O-Port Sound Enhancement for Acoustic Guitar, Large, Black",
            $formatter->buildNormalizedName(
                'Accessories - Potenciador de sonido O-Port de D\'Addario para guitarra acústica, grande, color negro.',
                'Accessories',
                'PW-OPBKL',
                null,
                'daddario',
                ['Nombre del Producto (Web)' => "D'Addario O-Port Sound Enhancement for Acoustic Guitar, Large, Black"]
            )
        );
    }

    public function test_it_normalizes_product_name_casing_without_breaking_codes_or_brands(): void
    {
        $formatter = new ProductTextFormatterService();

        $this->assertSame(
            'PROEL Cable Linea Jack-Jack 10 M. ESO130LU10 BK',
            $formatter->normalizeProductNameCasing('PROEL CABLE LINEA JACK-JACK 10 M. ESO130LU10 BK', 'PROEL')
        );

        $this->assertSame(
            'GEWA Soprano Pineapple Ukulele Manoa K-PA-BBH',
            $formatter->normalizeProductNameCasing('GEWA SOPRANO PINEAPPLE UKULELE MANOA K-PA-BBH', 'GEWA')
        );

        $this->assertSame(
            "D'Addario XLR Female to 1/4 Inch Female Balanced Adaptor",
            $formatter->normalizeProductNameCasing("D'ADDARIO XLR FEMALE TO 1/4 INCH FEMALE BALANCED ADAPTOR", "D'Addario")
        );

        $this->assertSame(
            'AMADEUS ACT302 Corneta en DO',
            $formatter->normalizeProductNameCasing('AMADEUS ACT302 CORNETA EN DO', 'AMADEUS')
        );

        $this->assertSame(
            'Gretsch® Patch Jacket, Black, XXL',
            $formatter->normalizeProductNameCasing('GRETSCH ® PATCH JACKET, BLACK, XXL', 'Gretsch')
        );
    }
}
