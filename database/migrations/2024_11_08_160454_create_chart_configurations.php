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
        Schema::create('chart_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('chart_type'); // E.g., 'line', 'bar', etc.
            $table->json('default_settings'); // Stores chart settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_configurations');
    }
};
