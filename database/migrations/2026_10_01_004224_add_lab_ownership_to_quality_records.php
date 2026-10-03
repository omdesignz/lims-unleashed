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
        $tables = ['complaints', 'management_reviews', 'uncertainty_sources', 'environmental_conditions'];

        foreach ($tables as $tableName) {
            if (DB::table($tableName)->exists()) {
                throw new RuntimeException("Assign an owning laboratory to retained {$tableName} records before migrating.");
            }
        }

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
                $table->index(['lab_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['complaints', 'management_reviews', 'uncertainty_sources', 'environmental_conditions'];

        foreach ($tables as $tableName) {
            if (DB::table($tableName)->exists()) {
                throw new RuntimeException("Cannot remove laboratory ownership while {$tableName} records exist.");
            }
        }

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['lab_id', 'created_at']);
                $table->dropConstrainedForeignId('lab_id');
            });
        }
    }
};
