<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('normalized_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url_original')->nullable();
            $table->string('path_local');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('status', 30)->default('pending_download');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index('master_product_id');
            $table->index('normalized_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
