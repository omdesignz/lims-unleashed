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
        Schema::create('environmental_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('area');
            $table->string('location')->nullable();
            $table->timestamp('recorded_at');
            $table->decimal('temperature_c', 6, 2)->nullable();
            $table->decimal('humidity_percent', 6, 2)->nullable();
            $table->decimal('pressure_kpa', 8, 2)->nullable();
            $table->decimal('co2_ppm', 8, 2)->nullable();
            $table->decimal('temperature_min_c', 6, 2)->nullable();
            $table->decimal('temperature_max_c', 6, 2)->nullable();
            $table->decimal('humidity_min_percent', 6, 2)->nullable();
            $table->decimal('humidity_max_percent', 6, 2)->nullable();
            $table->string('status')->default('within_limits');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environmental_conditions');
    }
};
