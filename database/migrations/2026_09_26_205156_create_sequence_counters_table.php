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
        Schema::create('sequence_counters', function (Blueprint $table) {
            $table->string('scope_hash', 64)->primary();
            $table->string('table_name');
            $table->string('field_name');
            $table->json('scope_values');
            $table->unsignedBigInteger('last_value')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_counters');
    }
};
