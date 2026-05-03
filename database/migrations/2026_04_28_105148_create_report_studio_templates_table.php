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
        Schema::create('report_studio_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('studio_type');
            $table->string('renderer')->default('internal');
            $table->string('status')->default('draft');
            $table->boolean('is_default')->default(false);
            $table->string('theme_preset')->nullable();
            $table->string('canva_design_url')->nullable();
            $table->text('description')->nullable();
            $table->json('layout_schema')->nullable();
            $table->json('export_settings')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['studio_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_studio_templates');
    }
};
