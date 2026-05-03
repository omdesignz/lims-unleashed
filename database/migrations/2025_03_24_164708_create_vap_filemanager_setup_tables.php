<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('v_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['file', 'folder']);
            $table->bigInteger('size')->nullable();
            $table->timestamp('modified_at');
            $table->uuid('parent_id')->nullable();
            $table->string('mime_type')->nullable();
            $table->binary('content')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('v_files')->onDelete('cascade');
        });

        Schema::create('v_file_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->binary('content');
            $table->foreignId('created_by')->constrained('users');
            $table->string('comment')->nullable();
            $table->timestamps();
            
            $table->foreign('file_id')->references('id')->on('v_files')->onDelete('cascade');
        });

        Schema::create('v_file_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->foreignId('user_id')->constrained();
            $table->enum('access_level', ['read', 'write', 'admin']);
            $table->timestamps();
            
            $table->foreign('file_id')->references('id')->on('v_files')->onDelete('cascade');
        });

        Schema::create('v_file_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('file_id');
            $table->foreignId('shared_with')->constrained('users');
            $table->timestamps();
            
            $table->foreign('file_id')->references('id')->on('v_files')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v_file_shares');
        Schema::dropIfExists('v_file_permissions');
        Schema::dropIfExists('v_file_versions');
        Schema::dropIfExists('v_files');
    }
};