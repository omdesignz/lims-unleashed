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
        if (Schema::hasTable('inventory_need_items')) {
            if (DB::table('inventory_need_items')->count() === 0) {
                Schema::drop('inventory_need_items');
            } else {
                DB::statement('ALTER TABLE inventory_need_items ADD INDEX inventory_need_items_inventory_need_id_foreign (inventory_need_id)');
                DB::statement('ALTER TABLE inventory_need_items ADD INDEX inventory_need_items_inventory_item_id_foreign (inventory_item_id)');
                DB::statement('ALTER TABLE inventory_need_items ADD INDEX inventory_need_items_warehouse_id_foreign (warehouse_id)');
                DB::statement('ALTER TABLE inventory_need_items ADD CONSTRAINT inventory_need_items_inventory_need_id_foreign FOREIGN KEY (inventory_need_id) REFERENCES inventory_needs(id) ON DELETE CASCADE');
                DB::statement('ALTER TABLE inventory_need_items ADD CONSTRAINT inventory_need_items_inventory_item_id_foreign FOREIGN KEY (inventory_item_id) REFERENCES i_items(id)');
                DB::statement('ALTER TABLE inventory_need_items ADD CONSTRAINT inventory_need_items_warehouse_id_foreign FOREIGN KEY (warehouse_id) REFERENCES i_warehouses(id) ON DELETE SET NULL');

                return;
            }
        }

        Schema::create('inventory_need_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_need_id')->constrained('inventory_needs')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('i_items');
            $table->foreignId('warehouse_id')->nullable()->constrained('i_warehouses')->nullOnDelete();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_approved')->nullable();
            $table->unsignedInteger('quantity_received')->default(0);
            $table->decimal('estimated_unit_price', 18, 2)->nullable();
            $table->string('status')->default('requested');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_need_items');
    }
};
