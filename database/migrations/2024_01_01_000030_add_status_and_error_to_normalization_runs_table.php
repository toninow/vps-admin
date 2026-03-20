<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalization_runs', function (Blueprint $table) {
            $table->string('status', 32)->default('running')->after('import_id');
            $table->text('error_message')->nullable()->after('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('normalization_runs', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
        });
    }
};
