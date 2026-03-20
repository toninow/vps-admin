<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_imports', function (Blueprint $table) {
            $table->unsignedSmallInteger('catalog_year')->nullable()->after('file_type');
            $table->index(['supplier_id', 'catalog_year']);
            $table->index(['catalog_year', 'created_at']);
        });

        DB::table('supplier_imports')
            ->whereNull('catalog_year')
            ->update([
                'catalog_year' => DB::raw('YEAR(created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('supplier_imports', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'catalog_year']);
            $table->dropIndex(['catalog_year', 'created_at']);
            $table->dropColumn('catalog_year');
        });
    }
};
