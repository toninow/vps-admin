<?php

namespace Tests\Unit;

use App\Models\NormalizedProduct;
use App\Services\Normalization\EanIssueService;
use Tests\TestCase;

class EanIssueServiceTest extends TestCase
{
    public function test_it_classifies_invalid_raw_barcode_instead_of_marking_it_as_empty(): void
    {
        $service = new class extends EanIssueService
        {
            public function comparableValue(NormalizedProduct $product): string
            {
                return $this->extractComparableValue($product);
            }

            public function issueFor(NormalizedProduct $product): ?string
            {
                $value = $this->extractComparableValue($product);

                return $this->classifyProductIssue(
                    $product,
                    $value,
                    strtolower((string) $product->barcode_status),
                    strtolower((string) $product->barcode_type)
                );
            }
        };

        $product = new NormalizedProduct([
            'ean13' => null,
            'barcode_raw' => '668808240200',
            'barcode_status' => 'invalid_ean',
            'barcode_type' => 'ean13',
        ]);

        $this->assertSame('668808240200', $service->comparableValue($product));
        $this->assertSame(EanIssueService::TYPE_UPC_OR_OTHER, $service->issueFor($product));
    }

    public function test_it_marks_non_digit_raw_values_as_invalid_chars(): void
    {
        $service = new EanIssueService();

        $this->assertSame(EanIssueService::TYPE_INVALID_CHARS, $service->classifyEan('---'));
        $this->assertSame(EanIssueService::TYPE_INVALID_LENGTH, $service->classifyEan('MX-883'));
    }
}
