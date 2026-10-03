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
        DB::statement("UPDATE item_categories SET inventory_type = CASE WHEN id = 1 THEN 'equipment' ELSE 'material' END WHERE inventory_type IS NULL");
        DB::statement('INSERT INTO inventory_category_usage (category_id) SELECT DISTINCT category_id FROM i_items WHERE category_id IS NOT NULL ON CONFLICT DO NOTHING');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Classification is retained until the companion schema migration is rolled back.
    }
};
