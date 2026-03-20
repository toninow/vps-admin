<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_category_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('normalized_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('auto');
            $table->decimal('score', 8, 4)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_suggestions');
    }
};
