<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->string('range')->nullable();
            $table->text('calibration_points')->nullable();
            $table->string('calibration_status')->nullable();
            $table->string('calibration_certificate_no')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('maintenance_tasks')
            ->whereNotNull('range')
            ->orWhereNotNull('calibration_points')
            ->orWhereNotNull('calibration_status')
            ->orWhereNotNull('calibration_certificate_no')
            ->exists()) {
            throw new RuntimeException('Cannot remove retained maintenance calibration details.');
        }

        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->dropColumn(['range', 'calibration_points', 'calibration_status', 'calibration_certificate_no']);
        });
    }
};
