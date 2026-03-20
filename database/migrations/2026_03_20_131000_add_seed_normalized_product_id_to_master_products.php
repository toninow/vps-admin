<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->unsignedBigInteger('seed_normalized_product_id')->nullable()->after('ean13');
            $table->index('seed_normalized_product_id', 'master_products_seed_normalized_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropIndex('master_products_seed_normalized_index');
            $table->dropColumn('seed_normalized_product_id');
        });
    }
};
