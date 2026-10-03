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
        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('lab_id')->nullable()->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'id']);
            $table->index(['lab_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex(['lab_id', 'status']);
            $table->dropIndex(['lab_id', 'id']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
