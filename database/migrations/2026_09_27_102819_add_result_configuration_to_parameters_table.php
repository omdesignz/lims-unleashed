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
        Schema::table('parameters', function (Blueprint $table) {
            $table->string('optimal_analysis_time')->nullable();
            $table->string('result_type', 32)->nullable();
            $table->unsignedSmallInteger('decimal_places')->nullable();
            $table->boolean('requires_calculation')->default(false);
            $table->text('formula_expression')->nullable();
            $table->json('calculation_parameters')->nullable();
            $table->json('variables')->nullable();
            $table->foreignId('formula_id')->nullable()->constrained('formulas')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formula_id');
            $table->dropColumn(['optimal_analysis_time', 'result_type', 'decimal_places', 'requires_calculation', 'formula_expression', 'calculation_parameters', 'variables']);
        });
    }
};
