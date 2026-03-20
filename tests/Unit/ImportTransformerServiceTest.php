<?php

namespace Tests\Unit;

use App\Services\Import\BarcodeClassifierService;
use App\Services\Import\ImportTransformerService;
use App\Services\Normalization\ProductTextFormatterService;
use PHPUnit\Framework\TestCase;

class ImportTransformerServiceTest extends TestCase
{
    public function test_it_builds_full_category_path_from_numbered_columns_for_adagio(): void
    {
        $service = new ImportTransformerServiceProbe(
            new BarcodeClassifierService(),
            new ProductTextFormatterService()
        );

        $path = $service->probeResolveCategoryPathExport(
            'Guitarras',
            [
                'categoria_1' => 'Instrumentos',
                'categoria_2' => 'Guitarras',
                'categoria_3' => 'Electricas',
            ],
            [
                'category_path_export' => 'categoria_1',
            ],
            'adagio'
        );

        $this->assertSame('Instrumentos, Guitarras, Electricas', $path);
    }

    public function test_it_can_build_a_path_from_an_explicit_composite_mapping(): void
    {
        $service = new ImportTransformerServiceProbe(
            new BarcodeClassifierService(),
            new ProductTextFormatterService()
        );

        $path = $service->probeResolveCategoryPathExport(
            '',
            [
                'familia' => 'Audio',
                'subfamilia' => 'Microfonos',
            ],
            [
                'category_path_export' => 'familia | subfamilia',
            ],
            null
        );

        $this->assertSame('Audio, Microfonos', $path);
    }
}

class ImportTransformerServiceProbe extends ImportTransformerService
{
    public function probeResolveCategoryPathExport(
        mixed $mappedValue,
        array $raw,
        array $columnsMap,
        ?string $supplierSlug = null
    ): string {
        return $this->resolveCategoryPathExport($mappedValue, $raw, $columnsMap, $supplierSlug);
    }
}
