<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->enum('type', ['review', 'approve', 'publish']);
            $table->foreignId('assigned_to')->constrained('users');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('file_id')->references('id')->on('v_files')->onDelete('cascade');
        });

        Schema::create('workflow_task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->text('comment');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->foreign('task_id')->references('id')->on('workflow_tasks')->onDelete('cascade');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('file_tags', function (Blueprint $table) {
            $table->uuid('file_id');
            $table->uuid('tag_id');
            $table->timestamps();
            
            $table->primary(['file_id', 'tag_id']);
            $table->foreign('file_id')->references('id')->on('v_files')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('workflow_task_comments');
        Schema::dropIfExists('workflow_tasks');
    }
};