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
        if (DB::table('proficiency_tests')->exists()) {
            throw new RuntimeException('Assign an owning laboratory to retained proficiency tests before migrating.');
        }

        Schema::table('proficiency_tests', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['lab_id', 'name']);
            $table->index(['lab_id', 'status', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('proficiency_tests')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership while proficiency tests exist.');
        }

        Schema::table('proficiency_tests', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'status', 'date']);
            $table->dropUnique(['lab_id', 'name']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
