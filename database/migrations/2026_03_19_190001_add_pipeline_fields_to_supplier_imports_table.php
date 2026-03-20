<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_imports', function (Blueprint $table) {
            $table->string('pipeline_status', 30)->default('idle')->after('status');
            $table->string('pipeline_stage', 30)->nullable()->after('pipeline_status');
            $table->unsignedInteger('pipeline_total')->default(0)->after('pipeline_stage');
            $table->unsignedInteger('pipeline_processed')->default(0)->after('pipeline_total');
            $table->decimal('pipeline_percent', 5, 2)->default(0)->after('pipeline_processed');
            $table->string('pipeline_message')->nullable()->after('pipeline_percent');
            $table->timestamp('pipeline_started_at')->nullable()->after('pipeline_message');
            $table->timestamp('pipeline_finished_at')->nullable()->after('pipeline_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_imports', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_status',
                'pipeline_stage',
                'pipeline_total',
                'pipeline_processed',
                'pipeline_percent',
                'pipeline_message',
                'pipeline_started_at',
                'pipeline_finished_at',
            ]);
        });
    }
};
