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
        Schema::create('maintenance_task_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->char('file_hash', 64);
            $table->unsignedSmallInteger('row_count');
            $table->jsonb('task_ids');
            $table->timestamps();
            $table->index(['lab_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_task_imports');
    }
};
