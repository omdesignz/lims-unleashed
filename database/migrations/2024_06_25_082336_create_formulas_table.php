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
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('expression'); // Stores the formula expression
            $table->timestamps();
        });

        Schema::table('parameter_profile', function (Blueprint $table): void {
            $table->foreign('formula_id')->references('id')->on('formulas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parameter_profile', function (Blueprint $table): void {
            $table->dropForeign(['formula_id']);
        });

        Schema::dropIfExists('formulas');
    }
};
