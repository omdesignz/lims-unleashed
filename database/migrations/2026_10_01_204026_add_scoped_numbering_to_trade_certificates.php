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
        DB::transaction(function (): void {
            foreach (['import_certificates', 'export_certificates'] as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->unsignedSmallInteger('certificate_year')->nullable();
                    $table->unsignedBigInteger('seq')->nullable();
                    $table->unique(['lab_id', 'certificate_year', 'seq'], $name.'_lab_year_seq_unique');
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (['import_certificates', 'export_certificates'] as $name) {
                DB::statement('LOCK TABLE "'.$name.'" IN ACCESS EXCLUSIVE MODE');
            }
            foreach (['import_certificates', 'export_certificates'] as $name) {
                if (DB::table($name)->whereNotNull('certificate_year')->orWhereNotNull('seq')->exists()) {
                    throw new LogicException('Issued trade certificate numbering must be preserved.');
                }
            }
            foreach (['import_certificates', 'export_certificates'] as $name) {
                Schema::table($name, function (Blueprint $table) use ($name): void {
                    $table->dropUnique($name.'_lab_year_seq_unique');
                    $table->dropColumn(['certificate_year', 'seq']);
                });
            }
        });
    }
};
