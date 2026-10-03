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
        if (DB::table('v_files')->exists()) {
            throw new RuntimeException('Assign retained documents to laboratories before adding file ownership.');
        }

        Schema::table('v_files', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('v_files')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership from retained documents.');
        }

        Schema::table('v_files', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'parent_id']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
