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
        if (DB::table('i_inventory_batches')->exists()
            || DB::table('itransactions')->exists()
            || DB::table('reagent_consumption')->exists()) {
            throw new RuntimeException('Resolve retained batch, ledger, and consumption ownership before enforcing laboratory ownership.');
        }

        Schema::table('inventory', function (Blueprint $table): void {
            $table->unique(['id', 'lab_id'], 'inventory_id_lab_unique');
        });

        Schema::table('i_inventory_batches', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['id', 'lab_id'], 'i_inventory_batches_id_lab_unique');
            $table->foreign(['inventory_id', 'lab_id'], 'i_inventory_batches_inventory_lab_fk')
                ->references(['id', 'lab_id'])->on('inventory')->restrictOnDelete();
        });

        Schema::table('itransactions', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->unique(['id', 'lab_id'], 'itransactions_id_lab_unique');
            $table->index(['lab_id', 'deleted_at']);
            $table->foreign(['inventory_id', 'lab_id'], 'itransactions_inventory_lab_fk')
                ->references(['id', 'lab_id'])->on('inventory')->restrictOnDelete();
            $table->foreign(['item_id', 'lab_id'], 'itransactions_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'lab_id'], 'itransactions_warehouse_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
            $table->foreign(['batch_id', 'lab_id'], 'itransactions_batch_lab_fk')
                ->references(['id', 'lab_id'])->on('i_inventory_batches')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE reagent_consumption ALTER COLUMN warehouse_id SET NOT NULL');
        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->foreignId('lab_id')->constrained('labs')->restrictOnDelete();
            $table->index(['lab_id', 'date']);
            $table->foreign(['reagent_id', 'lab_id'], 'reagent_consumption_item_lab_fk')
                ->references(['id', 'lab_id'])->on('i_items')->restrictOnDelete();
            $table->foreign(['warehouse_id', 'lab_id'], 'reagent_consumption_warehouse_lab_fk')
                ->references(['id', 'lab_id'])->on('i_warehouses')->restrictOnDelete();
            $table->foreign(['batch_id', 'lab_id'], 'reagent_consumption_batch_lab_fk')
                ->references(['id', 'lab_id'])->on('i_inventory_batches')->restrictOnDelete();
            $table->foreign(['inventory_transaction_id', 'lab_id'], 'reagent_consumption_transaction_lab_fk')
                ->references(['id', 'lab_id'])->on('itransactions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_inventory_batches')->exists()
            || DB::table('itransactions')->exists()
            || DB::table('reagent_consumption')->exists()) {
            throw new RuntimeException('Cannot remove ledger laboratory ownership while records are retained.');
        }

        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->dropForeign('reagent_consumption_transaction_lab_fk');
            $table->dropForeign('reagent_consumption_batch_lab_fk');
            $table->dropForeign('reagent_consumption_warehouse_lab_fk');
            $table->dropForeign('reagent_consumption_item_lab_fk');
            $table->dropIndex(['lab_id', 'date']);
            $table->dropConstrainedForeignId('lab_id');
        });
        DB::statement('ALTER TABLE reagent_consumption ALTER COLUMN warehouse_id DROP NOT NULL');

        Schema::table('itransactions', function (Blueprint $table): void {
            $table->dropForeign('itransactions_batch_lab_fk');
            $table->dropForeign('itransactions_warehouse_lab_fk');
            $table->dropForeign('itransactions_item_lab_fk');
            $table->dropForeign('itransactions_inventory_lab_fk');
            $table->dropIndex(['lab_id', 'deleted_at']);
            $table->dropUnique('itransactions_id_lab_unique');
            $table->dropConstrainedForeignId('lab_id');
        });

        Schema::table('i_inventory_batches', function (Blueprint $table): void {
            $table->dropForeign('i_inventory_batches_inventory_lab_fk');
            $table->dropUnique('i_inventory_batches_id_lab_unique');
            $table->dropConstrainedForeignId('lab_id');
        });

        Schema::table('inventory', function (Blueprint $table): void {
            $table->dropUnique('inventory_id_lab_unique');
        });
    }
};
