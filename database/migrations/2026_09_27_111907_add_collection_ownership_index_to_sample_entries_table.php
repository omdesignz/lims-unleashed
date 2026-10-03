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
        Schema::table('sample_entries', function (Blueprint $table) {
            $table->index(['collection_product_id', 'lab_id', 'deleted_at'], 'sample_entries_collection_ownership_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sample_entries', function (Blueprint $table) {
            $table->dropIndex('sample_entries_collection_ownership_index');
        });
    }
};
