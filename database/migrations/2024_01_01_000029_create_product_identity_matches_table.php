<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_identity_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('normalized_product_id');
            $table->unsignedBigInteger('master_product_id');
            $table->string('match_type', 32);
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamps();

            $table->foreign('normalized_product_id')
                ->references('id')
                ->on('normalized_products')
                ->onDelete('cascade');

            $table->foreign('master_product_id')
                ->references('id')
                ->on('master_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_identity_matches');
    }
};

