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
                Schema::table('inventory_need_items', function (Blueprint $table): void {
                    $table->index('inventory_need_id');
                    $table->index('inventory_item_id');
                    $table->index('warehouse_id');
                    $table->foreign('inventory_need_id')->references('id')->on('inventory_needs')->cascadeOnDelete();
                    $table->foreign('inventory_item_id')->references('id')->on('i_items');
                    $table->foreign('warehouse_id')->references('id')->on('i_warehouses')->nullOnDelete();
                });

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
