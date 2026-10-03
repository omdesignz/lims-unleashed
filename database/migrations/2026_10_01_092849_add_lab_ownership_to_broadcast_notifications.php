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
        if (DB::table('broadcast_notifications')->exists()) {
            throw new RuntimeException('Assign an owning laboratory to retained broadcast notifications before migrating.');
        }

        Schema::table('broadcast_notifications', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('broadcast_notifications')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership while broadcast notifications exist.');
        }

        Schema::table('broadcast_notifications', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'created_at']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
