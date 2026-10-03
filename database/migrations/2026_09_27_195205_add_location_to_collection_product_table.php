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
        if (! Schema::hasColumn('collection_product', 'location')) {
            Schema::table('collection_product', function (Blueprint $table): void {
                $table->string('location')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy installations may already contain location; preserve recorded sample locations.
    }
};
