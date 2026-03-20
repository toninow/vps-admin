<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('ean13', 13);
            $table->foreignId('master_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending_review');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_product_groups');
    }
};
