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
        Schema::create('reagent_consumption', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedInteger('reagent_id');
            $table->string('reagent_name');
            $table->decimal('quantity_used', 10, 2);
            $table->string('used_by')->nullable(); // Technician or system process
            $table->dateTime('used_at');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reagent_consumption');
    }
};
