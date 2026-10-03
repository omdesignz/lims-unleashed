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
        DB::statement('ALTER TABLE i_order_details DROP COLUMN total_price');
        DB::statement('ALTER TABLE i_order_details ALTER COLUMN qty TYPE NUMERIC(18,4), ALTER COLUMN received_qty TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE i_order_details ADD COLUMN total_price NUMERIC(20,4) GENERATED ALWAYS AS (qty * unit_price) STORED');
        DB::statement('ALTER TABLE i_order_details ADD CONSTRAINT i_order_details_quantities_check CHECK (qty > 0 AND received_qty >= 0 AND received_qty <= qty)');

        DB::statement('ALTER TABLE inventory_need_items ALTER COLUMN quantity_requested TYPE NUMERIC(18,4), ALTER COLUMN quantity_approved TYPE NUMERIC(18,4), ALTER COLUMN quantity_received TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE inventory_need_items ADD CONSTRAINT inventory_need_items_quantities_check CHECK (quantity_requested > 0 AND (quantity_approved IS NULL OR quantity_approved >= 0) AND quantity_received >= 0)');

        DB::statement('ALTER TABLE i_delivery_details ALTER COLUMN qty TYPE NUMERIC(18,4)');
        DB::statement('ALTER TABLE i_delivery_details ADD CONSTRAINT i_delivery_details_quantity_positive_check CHECK (qty > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_order_details')->exists()
            || DB::table('inventory_need_items')->exists()
            || DB::table('i_delivery_details')->exists()) {
            throw new RuntimeException('Cannot remove fractional procurement quantities while records are retained.');
        }

        DB::statement('ALTER TABLE i_delivery_details DROP CONSTRAINT i_delivery_details_quantity_positive_check');
        DB::statement('ALTER TABLE i_delivery_details ALTER COLUMN qty TYPE INTEGER');

        DB::statement('ALTER TABLE inventory_need_items DROP CONSTRAINT inventory_need_items_quantities_check');
        DB::statement('ALTER TABLE inventory_need_items ALTER COLUMN quantity_requested TYPE INTEGER, ALTER COLUMN quantity_approved TYPE INTEGER, ALTER COLUMN quantity_received TYPE INTEGER');

        DB::statement('ALTER TABLE i_order_details DROP CONSTRAINT i_order_details_quantities_check');
        DB::statement('ALTER TABLE i_order_details DROP COLUMN total_price');
        DB::statement('ALTER TABLE i_order_details ALTER COLUMN qty TYPE INTEGER, ALTER COLUMN received_qty TYPE INTEGER');
        DB::statement('ALTER TABLE i_order_details ADD COLUMN total_price NUMERIC(20,4) GENERATED ALWAYS AS (qty * unit_price) STORED');
    }
};
