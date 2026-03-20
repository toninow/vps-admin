<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalized_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_import_row_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('ean13', 13)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price_tax_incl', 12, 4)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->unsignedTinyInteger('tax_rule_id')->default(1);
            $table->string('warehouse', 50)->default('CARPETANA');
            $table->unsignedTinyInteger('active')->default(1);
            $table->string('brand')->nullable();
            $table->text('category_path_export')->nullable();
            $table->text('tags')->nullable();
            $table->string('validation_status', 30)->default('pending');
            $table->string('ean_status', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('normalized_products', function (Blueprint $table) {
            $table->index('supplier_import_id');
            $table->index('master_product_id');
            $table->index('ean13');
            $table->index(['supplier_id', 'supplier_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalized_products');
    }
};
