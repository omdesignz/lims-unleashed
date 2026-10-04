<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('v_non_conformities', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['lab_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('v_non_conformities', function (Blueprint $table) {
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
