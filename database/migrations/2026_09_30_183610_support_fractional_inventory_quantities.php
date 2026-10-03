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
        DB::statement('ALTER TABLE inventory ALTER COLUMN qty_available TYPE NUMERIC(18,4), ALTER COLUMN min_stock_level TYPE NUMERIC(18,4), ALTER COLUMN reorder_point TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE i_transfers ALTER COLUMN qty TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE reagent_consumption ALTER COLUMN quantity_used TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE inventory ADD CONSTRAINT inventory_thresholds_nonnegative_check CHECK (min_stock_level >= 0 AND reorder_point >= 0)');
        DB::statement('ALTER TABLE i_inventory_batches ADD CONSTRAINT i_inventory_batches_quantities_nonnegative_check CHECK (qty_received >= 0 AND qty_remaining >= 0)');
        DB::statement('ALTER TABLE reagent_consumption ADD CONSTRAINT reagent_consumption_quantity_positive_check CHECK (quantity_used > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('inventory')->exists()
            || DB::table('i_transfers')->exists()
            || DB::table('i_inventory_batches')->exists()
            || DB::table('reagent_consumption')->exists()) {
            throw new RuntimeException('Cannot remove fractional inventory quantities while records are retained.');
        }

        DB::statement('ALTER TABLE reagent_consumption DROP CONSTRAINT reagent_consumption_quantity_positive_check');
        DB::statement('ALTER TABLE i_inventory_batches DROP CONSTRAINT i_inventory_batches_quantities_nonnegative_check');
        DB::statement('ALTER TABLE inventory DROP CONSTRAINT inventory_thresholds_nonnegative_check');
        DB::statement('ALTER TABLE reagent_consumption ALTER COLUMN quantity_used TYPE NUMERIC(10,2)');
        DB::statement('ALTER TABLE i_transfers ALTER COLUMN qty TYPE INTEGER');
        DB::statement('ALTER TABLE inventory ALTER COLUMN qty_available TYPE INTEGER, ALTER COLUMN min_stock_level TYPE INTEGER, ALTER COLUMN reorder_point TYPE INTEGER');
    }
};
