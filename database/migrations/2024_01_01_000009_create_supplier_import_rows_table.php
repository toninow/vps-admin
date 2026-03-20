<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::table('supplier_import_rows', function (Blueprint $table) {
            $table->index(['supplier_import_id', 'row_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_import_rows');
    }
};
