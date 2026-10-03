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
        DB::statement('ALTER TABLE item_categories ALTER COLUMN inventory_type SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('inventory_category_usage')->exists()) {
            throw new RuntimeException('Cannot remove category guards while retained first-use evidence exists.');
        }
        DB::statement('ALTER TABLE item_categories ALTER COLUMN inventory_type DROP NOT NULL');
    }
};
