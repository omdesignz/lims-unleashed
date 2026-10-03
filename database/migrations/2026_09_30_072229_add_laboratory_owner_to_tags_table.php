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
        if (DB::table('tags')->exists()) {
            throw new RuntimeException('Assign retained document tags to laboratories before adding ownership.');
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['lab_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('tags')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership from retained document tags.');
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['lab_id', 'name']);
            $table->dropConstrainedForeignId('lab_id');
            $table->unique('name');
        });
    }
};
