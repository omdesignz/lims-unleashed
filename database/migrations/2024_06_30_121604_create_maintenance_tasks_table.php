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
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_task_no')->nullable();
            $table->string('maintenance_task_year');
            $table->integer('seq')->nullable();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->constrained('maintenance_categories')->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained('i_items')->onDelete('cascade');
            $table->date('due_date');
            $table->date('previous_date')->nullable();
            $table->date('next_date')->nullable();
            $table->string('acceptance_criteria')->nullable();
            $table->string('periodicity')->nullable();
            $table->enum('periodicity_unit', ['hours', 'days', 'weeks', 'months', 'years'])->nullable();
            $table->boolean('executed_by_supplier')->default(false);
            $table->foreignId('supplier_id')->constrained('i_suppliers')->onDelete('cascade');
            $table->longText('obs')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_planned')->default(false);
            $table->boolean('is_executed')->default(false);
            $table->longText('result')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
    }
};
