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
        if (! Schema::hasColumn('analysis', 'product_id')) {
            Schema::table('analysis', function (Blueprint $table): void {
                $table->foreignId('product_id')->nullable()->constrained('products');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy installations may already contain product_id; preserve analysis provenance.
    }
};
