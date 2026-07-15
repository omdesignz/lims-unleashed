<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis', function (Blueprint $table): void {
            $table->index(['end_date', 'department_id', 'created_at'], 'analysis_export_queue_index');
            $table->index('col_date', 'analysis_export_col_date_index');
        });

        Schema::table('results', function (Blueprint $table): void {
            $table->index(['sample_id', 'deleted_at', 'inserted_date', 'verified_date'], 'results_export_insertion_index');
            $table->index(['sample_id', 'deleted_at', 'verified_date', 'approved_date'], 'results_export_approval_index');
        });
    }

    public function down(): void
    {
        Schema::table('analysis', function (Blueprint $table): void {
            $table->dropIndex('analysis_export_queue_index');
            $table->dropIndex('analysis_export_col_date_index');
        });

        Schema::table('results', function (Blueprint $table): void {
            $table->dropIndex('results_export_insertion_index');
            $table->dropIndex('results_export_approval_index');
        });
    }
};
