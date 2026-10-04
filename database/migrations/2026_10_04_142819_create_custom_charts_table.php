<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Charts a person builds on their analytics board. The definition names a
     * whitelisted dataset, measure and grouping; values are always computed
     * live, inside the laboratory the chart belongs to.
     */
    public function up(): void
    {
        Schema::create('custom_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('dataset', 40);
            $table->string('measure', 40);
            $table->string('dimension', 40);
            $table->string('split', 40)->nullable();
            $table->string('kind', 12);
            $table->string('period', 12);
            $table->jsonb('colors')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'lab_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_charts');
    }
};
