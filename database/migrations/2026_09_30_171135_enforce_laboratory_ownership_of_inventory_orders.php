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
        if (DB::table('i_orders')->exists()
            || DB::table('i_order_details')->exists()
            || DB::table('inventory_needs')->exists()
            || DB::table('inventory_need_items')->exists()) {
            throw new RuntimeException('Resolve retained procurement ownership before enforcing laboratory ownership.');
        }

        Schema::table('i_orders', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['id', 'lab_id'], 'i_orders_id_lab_unique');
            $table->index(['lab_id', 'deleted_at']);
        });

        Schema::table('i_order_details', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'deleted_at']);
            $table->foreign(['order_id', 'lab_id'], 'i_order_details_order_lab_fk')
                ->references(['id', 'lab_id'])->on('i_orders')->restrictOnDelete();
            $table->foreign(['item_id', 'lab_id'], 'i_order_details_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'lab_id'], 'i_order_details_warehouse_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
        });

        Schema::table('inventory_needs', function (Blueprint $table): void {
            $table->foreign('lab_id', 'inventory_needs_lab_fk')->references('id')->on('labs')->restrictOnDelete();
            $table->unique(['id', 'lab_id'], 'inventory_needs_id_lab_unique');
            $table->index(['lab_id', 'deleted_at']);
            $table->foreign(['inventory_order_id', 'lab_id'], 'inventory_needs_order_lab_fk')
                ->references(['id', 'lab_id'])->on('i_orders')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE inventory_needs ALTER COLUMN lab_id SET NOT NULL');

        Schema::table('inventory_need_items', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index('lab_id');
            $table->foreign(['inventory_need_id', 'lab_id'], 'inventory_need_items_need_lab_fk')
                ->references(['id', 'lab_id'])->on('inventory_needs')->restrictOnDelete();
            $table->foreign(['inventory_item_id', 'lab_id'], 'inventory_need_items_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'lab_id'], 'inventory_need_items_warehouse_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_orders')->exists()
            || DB::table('i_order_details')->exists()
            || DB::table('inventory_needs')->exists()
            || DB::table('inventory_need_items')->exists()) {
            throw new RuntimeException('Cannot remove procurement laboratory ownership while records are retained.');
        }

        Schema::table('inventory_need_items', function (Blueprint $table): void {
            $table->dropForeign('inventory_need_items_need_lab_fk');
            $table->dropForeign('inventory_need_items_item_lab_fk');
            $table->dropForeign('inventory_need_items_warehouse_lab_fk');
            $table->dropIndex(['lab_id']);
            $table->dropConstrainedForeignId('lab_id');
        });

        Schema::table('inventory_needs', function (Blueprint $table): void {
            $table->dropForeign('inventory_needs_order_lab_fk');
            $table->dropForeign('inventory_needs_lab_fk');
            $table->dropUnique('inventory_needs_id_lab_unique');
            $table->dropIndex(['lab_id', 'deleted_at']);
        });
        DB::statement('ALTER TABLE inventory_needs ALTER COLUMN lab_id DROP NOT NULL');

        Schema::table('i_order_details', function (Blueprint $table): void {
            $table->dropForeign('i_order_details_order_lab_fk');
            $table->dropForeign('i_order_details_item_lab_fk');
            $table->dropForeign('i_order_details_warehouse_lab_fk');
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropConstrainedForeignId('lab_id');
        });

        Schema::table('i_orders', function (Blueprint $table): void {
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropUnique('i_orders_id_lab_unique');
            $table->dropConstrainedForeignId('lab_id');
        });
    }
};
