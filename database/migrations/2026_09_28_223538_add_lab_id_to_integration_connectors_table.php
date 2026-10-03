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
        if (Schema::hasColumn('integration_connectors', 'lab_id')) {
            return;
        }

        if (DB::table('integration_connectors')->exists()) {
            throw new RuntimeException('Connector ownership must be reviewed before migrating retained connectors. No records were changed.');
        }

        Schema::table('integration_connectors', function (Blueprint $table) {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('integration_connectors')->exists()) {
            throw new RuntimeException('Cannot remove laboratory ownership from retained connectors.');
        }

        Schema::table('integration_connectors', function (Blueprint $table) {
            $table->dropIndex(['lab_id', 'status']);
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
