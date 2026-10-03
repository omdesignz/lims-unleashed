<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('i_warehouses')->whereNull('lab_id')->exists()
            || DB::table('i_transfers')->exists()
            || DB::table('inventory')->where(fn ($query) => $query->whereNull('item_id')->orWhereNull('warehouse_id'))->exists()
            || DB::table('inventory')->whereNull('deleted_at')
                ->select('item_id', 'warehouse_id')
                ->groupBy('item_id', 'warehouse_id')
                ->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Resolve retained unowned inventory and transfers before enforcing laboratory ownership.');
        }

        DB::statement('ALTER TABLE i_warehouses ALTER COLUMN lab_id SET NOT NULL');
        Schema::table('i_warehouses', function (Blueprint $table): void {
            $table->unique(['id', 'lab_id'], 'i_warehouses_id_lab_unique');
        });

        Schema::table('i_transfers', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->date('expected_date')->nullable();
            $table->index(['lab_id', 'created_at']);
            $table->foreign(['source_id', 'lab_id'], 'i_transfers_source_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
            $table->foreign(['destination_id', 'lab_id'], 'i_transfers_destination_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE i_transfers ALTER COLUMN item_id SET NOT NULL, ALTER COLUMN source_id SET NOT NULL, ALTER COLUMN destination_id SET NOT NULL');
        DB::statement('ALTER TABLE i_transfers ADD CONSTRAINT i_transfers_distinct_warehouses_check CHECK (source_id <> destination_id)');
        DB::statement('ALTER TABLE i_transfers ADD CONSTRAINT i_transfers_positive_qty_check CHECK (qty > 0)');

        DB::statement('ALTER TABLE inventory ALTER COLUMN item_id SET NOT NULL, ALTER COLUMN warehouse_id SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX inventory_active_item_warehouse_unique ON inventory (item_id, warehouse_id) WHERE deleted_at IS NULL');
        DB::statement('ALTER TABLE inventory ADD CONSTRAINT inventory_available_nonnegative_check CHECK (qty_available >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_warehouses')->exists() || DB::table('i_transfers')->exists() || DB::table('inventory')->exists()) {
            throw new RuntimeException('Cannot remove inventory laboratory ownership while inventory data is retained.');
        }

        DB::statement('ALTER TABLE inventory DROP CONSTRAINT inventory_available_nonnegative_check');
        DB::statement('DROP INDEX inventory_active_item_warehouse_unique');
        DB::statement('ALTER TABLE inventory ALTER COLUMN item_id DROP NOT NULL, ALTER COLUMN warehouse_id DROP NOT NULL');

        DB::statement('ALTER TABLE i_transfers DROP CONSTRAINT i_transfers_distinct_warehouses_check');
        DB::statement('ALTER TABLE i_transfers DROP CONSTRAINT i_transfers_positive_qty_check');
        DB::statement('ALTER TABLE i_transfers ALTER COLUMN item_id DROP NOT NULL, ALTER COLUMN source_id DROP NOT NULL, ALTER COLUMN destination_id DROP NOT NULL');
        Schema::table('i_transfers', function (Blueprint $table): void {
            $table->dropForeign('i_transfers_source_lab_fk');
            $table->dropForeign('i_transfers_destination_lab_fk');
            $table->dropIndex(['lab_id', 'created_at']);
            $table->dropConstrainedForeignId('lab_id');
            $table->dropColumn('expected_date');
        });

        Schema::table('i_warehouses', function (Blueprint $table): void {
            $table->dropUnique('i_warehouses_id_lab_unique');
        });
        DB::statement('ALTER TABLE i_warehouses ALTER COLUMN lab_id DROP NOT NULL');
    }
};
