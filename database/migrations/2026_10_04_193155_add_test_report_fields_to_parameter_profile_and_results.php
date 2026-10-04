<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a test report must state about each test (ISO/IEC 17025:2017,
     * 7.8.2.1 n and p, 7.8.3.1 c). The profile definition holds the
     * laboratory's decision for the test; the result keeps a copy taken when
     * the result is inserted, so a later change to the catalogue does not
     * rewrite reports already issued. A result's `accredited` stays null when
     * it predates these fields: the report then states nothing about it.
     */
    public function up(): void
    {
        Schema::table('parameter_profile', function (Blueprint $table) {
            $table->boolean('accredited')->default(false);
            $table->string('subcontractor')->nullable();
            $table->decimal('uncertainty_coverage_factor', 5, 2)->nullable();
        });

        Schema::table('results', function (Blueprint $table) {
            $table->boolean('accredited')->nullable();
            $table->string('subcontractor')->nullable();
            $table->decimal('uncertainty_coverage_factor', 5, 2)->nullable();
            $table->text('method_deviation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['accredited', 'subcontractor', 'uncertainty_coverage_factor', 'method_deviation']);
        });

        Schema::table('parameter_profile', function (Blueprint $table) {
            $table->dropColumn(['accredited', 'subcontractor', 'uncertainty_coverage_factor']);
        });
    }
};
