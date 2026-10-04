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
        Schema::table('customer_requests', function (Blueprint $table): void {
            $table->foreignId('lab_id')->nullable()->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'deleted_at', 'created_at'], 'customer_requests_lab_queue_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_requests', function (Blueprint $table): void {
            $table->dropIndex('customer_requests_lab_queue_index');
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
