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
        Schema::table('formulas', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
            $table->text('formula_expression')->nullable();
            $table->text('description')->nullable();
            $table->json('variables')->nullable();
            $table->string('category', 32)->nullable()->index();
            $table->string('output_unit', 50)->nullable();
            $table->unsignedSmallInteger('decimal_places')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formulas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['code', 'formula_expression', 'description', 'variables', 'category', 'output_unit', 'decimal_places', 'is_active']);
        });
    }
};
