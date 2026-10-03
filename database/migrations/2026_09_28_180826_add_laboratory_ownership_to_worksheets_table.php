<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('worksheets', 'lab_id')) {
            return;
        }

        if (DB::table('worksheets')->exists()) {
            throw new RuntimeException('Worksheet ownership must be reviewed before migrating retained worksheets. No records were changed.');
        }

        Schema::table('worksheets', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('analysis_id')->nullable()->unique()->constrained('analysis')->restrictOnDelete();
            $table->index(['lab_id', 'deleted_at', 'updated_at', 'id'], 'worksheets_lab_visibility_index');
        });
    }

    public function down(): void
    {
        if (DB::table('worksheets')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership from retained worksheets.');
        }

        Schema::table('worksheets', function (Blueprint $table): void {
            $table->dropIndex('worksheets_lab_visibility_index');
            $table->dropConstrainedForeignId('lab_id');
            $table->dropUnique(['analysis_id']);
            $table->dropConstrainedForeignId('analysis_id');
        });
    }
};
