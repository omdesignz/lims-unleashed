<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quality control samples and the results they feed into control charts.
     *
     * A catalogue product flagged as a control material (a reference material,
     * an internal control sample) makes every sample of it a quality control
     * sample. A chart tied to that product receives the approved results of
     * those samples for its parameter as points; a point keeps the result it
     * came from, so a result is plotted once.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_control_material')->default(false);
        });

        Schema::table('control_charts', function (Blueprint $table) {
            $table->foreignId('control_product_id')->nullable()->after('parameter_id')->constrained('products')->nullOnDelete();
        });

        Schema::table('control_chart_points', function (Blueprint $table) {
            $table->foreignId('result_id')->nullable()->constrained('results')->nullOnDelete();
            $table->foreignId('paired_result_id')->nullable()->constrained('results')->nullOnDelete();
            $table->foreignId('sample_entry_id')->nullable()->constrained('sample_entries')->nullOnDelete();

            $table->unique(['control_chart_id', 'result_id']);
            $table->unique(['control_chart_id', 'paired_result_id']);
        });
    }

    public function down(): void
    {
        Schema::table('control_chart_points', function (Blueprint $table) {
            $table->dropUnique(['control_chart_id', 'result_id']);
            $table->dropUnique(['control_chart_id', 'paired_result_id']);
            $table->dropConstrainedForeignId('result_id');
            $table->dropConstrainedForeignId('paired_result_id');
            $table->dropConstrainedForeignId('sample_entry_id');
        });

        Schema::table('control_charts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('control_product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_control_material');
        });
    }
};
