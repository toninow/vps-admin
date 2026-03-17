<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('public_url')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('local_path')->nullable();
            $table->string('project_type')->nullable();
            $table->string('framework')->nullable();
            $table->string('status')->default('development');
            $table->string('icon_path')->nullable();
            $table->string('color', 7)->nullable();
            $table->string('repository_url')->nullable();
            $table->text('technical_notes')->nullable();
            $table->boolean('has_mobile_app')->default(false);
            $table->boolean('has_api')->default(false);
            $table->json('main_endpoints')->nullable();
            $table->string('backend_version', 50)->nullable();
            $table->string('mobile_app_version', 50)->nullable();
            $table->string('sync_status')->default('unknown');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
