<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('normalized_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('master_product_suppliers', function (Blueprint $table) {
            $table->unique('normalized_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_product_suppliers');
    }
};
