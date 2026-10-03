<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parameter_profile', function (Blueprint $table): void {
            foreach (['unit', 'standard', 'nwp', 'category'] as $column) {
                $table->renameColumn($column, $column.'_label');
            }
            $table->string('protocol_label')->nullable();
            $table->string('formula_label')->nullable();
            $table->string('optimal_analysis_time')->nullable();
            $table->text('ref_val_origin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameter_profile', function (Blueprint $table): void {
            $table->dropColumn(['protocol_label', 'formula_label', 'optimal_analysis_time', 'ref_val_origin']);
            foreach (['unit', 'standard', 'nwp', 'category'] as $column) {
                $table->renameColumn($column.'_label', $column);
            }
        });
    }
};
