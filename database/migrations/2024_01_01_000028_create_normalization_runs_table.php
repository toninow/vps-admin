<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalization_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->integer('processed_products')->default(0);
            $table->integer('errors')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('import_id')
                ->references('id')
                ->on('supplier_imports')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalization_runs');
    }
};

