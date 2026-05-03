<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('capability');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('qualified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('authorized_from')->nullable();
            $table->date('authorized_until')->nullable();
            $table->date('training_completed_at')->nullable();
            $table->string('training_reference')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'capability', 'department_id'], 'personnel_qualifications_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_qualifications');
    }
};
