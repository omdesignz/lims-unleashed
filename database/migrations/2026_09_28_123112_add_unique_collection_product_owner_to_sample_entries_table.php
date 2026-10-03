<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE sample_entries ADD CONSTRAINT sample_entries_collection_product_unique UNIQUE (collection_product_id) DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE sample_entries DROP CONSTRAINT sample_entries_collection_product_unique');
    }
};
