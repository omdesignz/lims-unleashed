<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_entries', function (Blueprint $table): void {
            $table->index(['lab_id', 'id'], 'sample_entries_lab_queue_index');
            $table->index(['lab_id', 'status', 'id'], 'sample_entries_lab_status_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('sample_entries', function (Blueprint $table): void {
            $table->dropIndex('sample_entries_lab_queue_index');
            $table->dropIndex('sample_entries_lab_status_queue_index');
        });
    }
};
