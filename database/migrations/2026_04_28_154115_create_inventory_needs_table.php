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
        Schema::create('inventory_needs', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('lab_id')->nullable()->constrained('labs')->nullOnDelete();
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('inventory_order_id')->nullable()->constrained('i_orders')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->date('needed_by_date')->nullable();
            $table->text('justification')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_needs');
    }
};
