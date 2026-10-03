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
        Schema::create('quality_certificate_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_certificate_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('version', 32);
            $table->text('change_reason')->nullable();
            $table->string('change_type', 32);
            $table->text('change_description')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('is_current');
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('superseded_date')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->json('activity_log_ids')->nullable();
            $table->json('compliance_metadata')->nullable();
            $table->nullableMorphs('revisable');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['quality_certificate_id', 'revision_number'], 'certificate_revision_number_unique');
            $table->index(['quality_certificate_id', 'is_current'], 'certificate_revision_current_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_certificate_revisions');
    }
};
