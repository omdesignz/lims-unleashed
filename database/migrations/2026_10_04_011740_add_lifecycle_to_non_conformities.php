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
        Schema::table('v_non_conformities', function (Blueprint $table): void {
            $table->unsignedInteger('workflow_revision')->default(0);
            $table->jsonb('workflow_history')->nullable();
            $table->text('resolution_evidence')->nullable();
            $table->text('verification_evidence')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('v_non_conformities', function (Blueprint $table): void {
            $table->dropColumn(['workflow_revision', 'workflow_history', 'resolution_evidence', 'verification_evidence', 'closed_at']);
        });
    }
};
