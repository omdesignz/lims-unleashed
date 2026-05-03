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
        Schema::create('uncertainty_sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source_type');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('parameter_id')->nullable()->constrained('parameters')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('i_items')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('estimation_method')->nullable();
            $table->text('control_strategy')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uncertainty_sources');
    }
};
