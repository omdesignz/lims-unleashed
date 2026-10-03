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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('fixed_price', 10, 2)->default(0);
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->string('exemption_code')->nullable();
            $table->foreignId('tax_id')->nullable()->constrained('tax_types')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_id');
            $table->dropColumn(['price', 'fixed_price', 'tax_percentage', 'exemption_code']);
        });
    }
};
