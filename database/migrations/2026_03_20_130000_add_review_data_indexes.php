<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_category_suggestions', function (Blueprint $table) {
            $table->unique(['normalized_product_id', 'category_id'], 'pcs_normalized_category_unique');
            $table->index(['normalized_product_id', 'score'], 'pcs_normalized_score_index');
            $table->index('master_product_id', 'pcs_master_index');
        });

        Schema::table('product_ean_issues', function (Blueprint $table) {
            $table->index(['normalized_product_id', 'resolved_at'], 'pei_normalized_resolved_index');
            $table->index(['master_product_id', 'resolved_at'], 'pei_master_resolved_index');
            $table->index('issue_type', 'pei_issue_type_index');
        });

        Schema::table('product_identity_matches', function (Blueprint $table) {
            $table->index('normalized_product_id', 'pim_normalized_index');
            $table->index('master_product_id', 'pim_master_index');
            $table->index(['match_type', 'confidence_score'], 'pim_match_type_score_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_identity_matches', function (Blueprint $table) {
            $table->dropIndex('pim_normalized_index');
            $table->dropIndex('pim_master_index');
            $table->dropIndex('pim_match_type_score_index');
        });

        Schema::table('product_ean_issues', function (Blueprint $table) {
            $table->dropIndex('pei_normalized_resolved_index');
            $table->dropIndex('pei_master_resolved_index');
            $table->dropIndex('pei_issue_type_index');
        });

        Schema::table('product_category_suggestions', function (Blueprint $table) {
            $table->dropUnique('pcs_normalized_category_unique');
            $table->dropIndex('pcs_normalized_score_index');
            $table->dropIndex('pcs_master_index');
        });
    }
};
