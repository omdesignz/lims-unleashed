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
        Schema::create('v_non_conformities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('nc_number');
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('opened');
            $table->string('severity')->default('medium');
            $table->string('category')->default('quality');
            $table->string('sample_id')->nullable();
            $table->string('test_method')->nullable();
            $table->string('equipment_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('reported_by');
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_to')->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('occurrence_area')->nullable();
            $table->string('approved_by')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('was_effective')->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->text('evidence')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->text('preventive_actions')->nullable();
            $table->text('comments')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->unique(['id', 'lab_id']);
            $table->unique(['lab_id', 'nc_number']);
            $table->index(['lab_id', 'status', 'reported_at']);
            $table->index(['lab_id', 'severity', 'reported_at']);
        });

        Schema::create('v_non_conformity_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('nc_id');
            $table->text('correction')->nullable();
            $table->text('corrective_action')->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('was_effective')->nullable();
            $table->text('evidence')->nullable();
            $table->timestamps();

            $table->foreign(['nc_id', 'lab_id'])->references(['id', 'lab_id'])
                ->on('v_non_conformities')->cascadeOnDelete();
            $table->index(['lab_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v_non_conformity_actions');
        Schema::dropIfExists('v_non_conformities');
    }
};
