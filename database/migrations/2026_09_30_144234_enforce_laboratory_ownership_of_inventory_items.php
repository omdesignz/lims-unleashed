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
        if (DB::table('i_items')->exists() || DB::table('inventory')->exists() || DB::table('i_transfers')->exists()) {
            throw new RuntimeException('Resolve retained inventory item ownership before enforcing laboratory ownership.');
        }

        Schema::table('i_items', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['id', 'lab_id'], 'i_items_id_lab_unique');
            $table->index(['lab_id', 'deleted_at']);
            $table->dropUnique('i_items_code_unique');
            $table->dropUnique('i_items_barcode_unique');
            $table->unique(['lab_id', 'code']);
            $table->unique(['lab_id', 'barcode']);
            $table->unique(['lab_id', 'internal_code']);
        });

        Schema::table('inventory', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'deleted_at']);
            $table->foreign(['item_id', 'lab_id'], 'inventory_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'lab_id'], 'inventory_warehouse_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
        });

        Schema::table('i_transfers', function (Blueprint $table): void {
            $table->foreign(['item_id', 'lab_id'], 'i_transfers_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_items')->exists() || DB::table('inventory')->exists() || DB::table('i_transfers')->exists()) {
            throw new RuntimeException('Cannot remove inventory item laboratory ownership while inventory data is retained.');
        }

        Schema::table('i_transfers', function (Blueprint $table): void {
            $table->dropForeign('i_transfers_item_lab_fk');
        });

        Schema::table('inventory', function (Blueprint $table): void {
            $table->dropForeign('inventory_item_lab_fk');
            $table->dropForeign('inventory_warehouse_lab_fk');
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropConstrainedForeignId('lab_id');
        });

        Schema::table('i_items', function (Blueprint $table): void {
            $table->dropUnique(['lab_id', 'code']);
            $table->dropUnique(['lab_id', 'barcode']);
            $table->dropUnique(['lab_id', 'internal_code']);
            $table->unique('code');
            $table->unique('barcode');
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropUnique('i_items_id_lab_unique');
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
