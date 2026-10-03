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
        if (DB::table('personnel_qualifications')->exists()) {
            throw new RuntimeException('Assign an owning laboratory to retained qualifications before migrating.');
        }

        Schema::table('personnel_qualifications', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'user_id', 'capability']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('personnel_qualifications')->exists()) {
            throw new RuntimeException('Cannot remove qualification ownership while qualifications exist.');
        }

        Schema::table('personnel_qualifications', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'user_id', 'capability']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
