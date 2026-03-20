<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalization_runs', function (Blueprint $table) {
            $table->integer('total_products')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('normalization_runs', function (Blueprint $table) {
            $table->dropColumn('total_products');
        });
    }
};

