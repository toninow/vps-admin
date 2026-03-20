<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // completed si ya tiene finished_at, failed en caso contrario
        DB::table('normalization_runs')
            ->whereNotNull('finished_at')
            ->update(['status' => 'completed']);

        DB::table('normalization_runs')
            ->whereNull('finished_at')
            ->update(['status' => 'failed']);
    }

    public function down(): void
    {
        // No revertimos a running; dejamos los valores tal cual
    }
};

