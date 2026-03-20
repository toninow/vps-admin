<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();
        });

        Schema::table('stock_scan_events', function (Blueprint $table) {
            $table->index(['user_id', 'scanned_at']);
            $table->index('master_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_scan_events');
    }
};
