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
        Schema::create('integration_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('integration_connectors')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false)->index();
            $table->json('field_paths');
            $table->json('transformations')->nullable();
            $table->json('constants')->nullable();
            $table->timestamps();

            $table->unique(['connector_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_mappings');
    }
};
