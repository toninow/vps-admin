<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ean_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('normalized_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issue_type', 20);
            $table->string('value_received')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ean_issues');
    }
};
