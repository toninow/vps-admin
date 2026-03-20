<?php

namespace Tests\Unit;

use App\Models\MasterProduct;
use App\Models\Supplier;
use App\Models\SupplierImport;
use App\Models\NormalizedProduct;
use App\Services\Export\PrestashopProductCsvService;
use App\Services\Import\ImportFieldSemanticsService;
use App\Services\Normalization\ProductTextFormatterService;
use PHPUnit\Framework\TestCase;

class PrestashopProductCsvServiceTest extends TestCase
{
    public function test_it_formats_prestashop_price_columns_with_dot_decimal_and_six_decimals(): void
    {
        $service = new PrestashopProductCsvService(new ImportFieldSemanticsService(), new ProductTextFormatterService());

        $product = new MasterProduct([
            'reference' => 'REF-1',
            'ean13' => '8435040710008',
            'name' => 'Producto de prueba',
            'category_path_export' => 'Instrumentos/Prueba',
            'brand' => 'Marca',
            'quantity' => 7,
            'price_tax_incl' => 121,
            'cost_price' => 50,
            'tax_rule_id' => 1,
            'active' => 1,
        ]);

        $import = new SupplierImport([
            'mapping_snapshot' => [
                'columns_map' => [
                    'price_tax_incl' => 'PVP con IVA',
                    'cost_price' => 'Neto',
                ],
            ],
        ]);
        $import->id = 999;

        $sourceProduct = new NormalizedProduct();
        $sourceProduct->setRelation('supplierImport', $import);
        $sourceProduct->setRelation('supplier', new Supplier(['name' => 'Proveedor test']));

        $payload = $service->buildForMasterProduct($product, $sourceProduct, 21.0);

        $this->assertSame('50.000000', $payload['row']['Wholesale price']);
        $this->assertSame('100.000000', $payload['row']['Price tax excluded']);
        $this->assertSame('121.000000', $payload['row']['Price tax included']);
        $this->assertSame([], $payload['warnings']);
    }

    public function test_it_flags_missing_prices_for_export_review(): void
    {
        $service = new PrestashopProductCsvService(new ImportFieldSemanticsService(), new ProductTextFormatterService());

        $product = new MasterProduct([
            'reference' => 'REF-2',
            'name' => 'Sin precios',
            'active' => 1,
        ]);

        $import = new SupplierImport([
            'mapping_snapshot' => [
                'columns_map' => [
                    'price_tax_incl' => 'PVP',
                    'cost_price' => 'Neto',
                ],
            ],
        ]);
        $import->id = 1000;

        $sourceProduct = new NormalizedProduct();
        $sourceProduct->setRelation('supplierImport', $import);

        $payload = $service->buildForMasterProduct($product, $sourceProduct, 21.0);

        $this->assertSame('', $payload['row']['Wholesale price']);
        $this->assertSame('', $payload['row']['Price tax excluded']);
        $this->assertSame('', $payload['row']['Price tax included']);
        $this->assertContains('Falta precio de venta para PrestaShop.', $payload['warnings']);
        $this->assertContains('Falta precio de compra para PrestaShop.', $payload['warnings']);
    }
}
