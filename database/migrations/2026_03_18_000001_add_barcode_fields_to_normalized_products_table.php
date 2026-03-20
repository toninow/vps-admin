<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalized_products', function (Blueprint $table) {
            $table->string('barcode_raw')->nullable()->after('ean13');
            $table->string('barcode_type', 30)->nullable()->after('barcode_raw');
            $table->string('barcode_status', 30)->nullable()->after('barcode_type');
        });

        Schema::table('normalized_products', function (Blueprint $table) {
            $table->index('barcode_type');
            $table->index('barcode_status');
        });
    }

    public function down(): void
    {
        Schema::table('normalized_products', function (Blueprint $table) {
            $table->dropIndex(['barcode_type']);
            $table->dropIndex(['barcode_status']);
            $table->dropColumn(['barcode_raw', 'barcode_type', 'barcode_status']);
        });
    }
};

