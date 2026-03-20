<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->string('ean13', 13)->nullable()->unique();
            $table->string('reference')->nullable();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price_tax_incl', 12, 4)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->unsignedTinyInteger('tax_rule_id')->default(1);
            $table->string('warehouse', 50)->default('CARPETANA');
            $table->unsignedTinyInteger('active')->default(1);
            $table->string('brand')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('category_path_export')->nullable();
            $table->text('tags')->nullable();
            $table->text('search_keywords_normalized')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::table('master_products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('is_approved');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_products');
    }
};
