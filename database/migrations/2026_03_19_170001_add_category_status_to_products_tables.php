<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalized_products', function (Blueprint $table) {
            if (! Schema::hasColumn('normalized_products', 'category_status')) {
                $table->string('category_status', 20)->default('unassigned')->after('category_id');
                $table->index('category_status');
            }
        });

        Schema::table('master_products', function (Blueprint $table) {
            if (! Schema::hasColumn('master_products', 'category_status')) {
                $table->string('category_status', 20)->default('unassigned')->after('category_id');
                $table->index('category_status');
            }
        });

        DB::table('normalized_products')
            ->whereNotNull('category_id')
            ->update(['category_status' => 'suggested']);

        DB::table('master_products')
            ->whereNotNull('category_id')
            ->update(['category_status' => 'suggested']);
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            if (Schema::hasColumn('master_products', 'category_status')) {
                $table->dropIndex(['category_status']);
                $table->dropColumn('category_status');
            }
        });

        Schema::table('normalized_products', function (Blueprint $table) {
            if (Schema::hasColumn('normalized_products', 'category_status')) {
                $table->dropIndex(['category_status']);
                $table->dropColumn('category_status');
            }
        });
    }
};
