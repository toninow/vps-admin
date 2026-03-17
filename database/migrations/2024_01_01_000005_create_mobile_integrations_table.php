<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('app_name');
            $table->string('platform')->default('other');
            $table->string('current_version', 50);
            $table->string('min_supported_version', 50)->nullable();
            $table->string('api_url')->nullable();
            $table->text('integration_token')->nullable();
            $table->string('connection_status')->default('unknown');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_integrations');
    }
};
