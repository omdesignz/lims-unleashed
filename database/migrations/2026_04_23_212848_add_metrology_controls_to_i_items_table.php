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
        Schema::table('i_items', function (Blueprint $table) {
            $table->decimal('metrological_uncertainty_value', 12, 4)->nullable()->after('resolution');
            $table->string('metrological_uncertainty_unit')->nullable()->after('metrological_uncertainty_value');
            $table->string('metrological_traceability_reference')->nullable()->after('metrological_uncertainty_unit');
            $table->date('metrology_review_due_at')->nullable()->after('next_calibration_date');
            $table->text('metrology_notes')->nullable()->after('acceptance_criteria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('i_items', function (Blueprint $table) {
            $table->dropColumn([
                'metrological_uncertainty_value',
                'metrological_uncertainty_unit',
                'metrological_traceability_reference',
                'metrology_review_due_at',
                'metrology_notes',
            ]);
        });
    }
};
