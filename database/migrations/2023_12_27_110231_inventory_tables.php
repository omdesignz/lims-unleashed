<?php

use App\Enums\Orders\InventoryOrderItemStatus;
use App\Enums\Orders\InventoryOrderTrackingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Item Categories
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('code')->unique()->nullable();

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        // Item Units
        Schema::create('i_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'code']);
            $table->softDeletes();
            $table->timestamps();
        });

        // Item Types
        Schema::create('i_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();

            $table->index(['id', 'name']);

            $table->softDeletes();
            $table->timestamps();
        });

        // Suppliers
        Schema::create('i_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('address')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Items
        Schema::create('i_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('location')->nullable();
            $table->string('model')->nullable();
            $table->string('software')->nullable();
            $table->string('firmware')->nullable();
            $table->string('internal_code')->nullable();
            $table->string('range')->nullable();
            $table->string('precision')->nullable();
            $table->string('resolution')->nullable();
            $table->longText('description')->nullable();
            $table->longText('obs')->nullable();
            $table->string('code')->unique()->nullable();
            $table->string('barcode')->unique()->nullable();
            $table->decimal('reorder_qty', 10, 2)->default(0);
            $table->decimal('packed_weight', 10, 2)->default(0);
            $table->string('packed_weight_unit')->nullable();
            $table->decimal('packed_height', 10, 2)->default(0);
            $table->string('packed_height_unit')->nullable();
            $table->decimal('packed_width', 10, 2)->default(0);
            $table->string('packed_width_unit')->nullable();
            $table->decimal('packed_depth', 10, 2)->default(0);
            $table->string('packed_depth_unit')->nullable();
            $table->boolean('refrigerated')->default(false);
            $table->unsignedBigInteger('status_id')->nullable();
            $table->boolean('has_safety_documentation')->default(true);
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types');
            $table->foreignId('category_id')->nullable()->constrained('item_categories');
            $table->foreignId('unit_id')->nullable()->constrained('i_units');
            $table->foreignId('type_id')->nullable()->constrained('i_types');
            $table->string('lot')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('i_suppliers');
            $table->string('serial_number')->nullable();
            $table->date('last_calibration_date')->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->date('reagent_open_date')->nullable();
            $table->date('reagent_expiry_date')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });

        // Locations
        Schema::create('i_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->longText('address')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });


        // Item Warehouses
        Schema::create('i_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_refrigerated')->default(false);
            $table->boolean('is_ventilated')->default(false);
            $table->boolean('has_air_exhaustion')->default(false);
            $table->foreignId('location_id')->nullable()->constrained('i_locations');
            $table->softDeletes();
            $table->timestamps();
        });


        // Inventory
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->integer('qty_available')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('status')->default('AVAILABLE');
            $table->foreignId('warehouse_id')->nullable()->constrained('i_warehouses');
            $table->foreignId('item_id')->nullable()->constrained('i_items');
            $table->softDeletes();
            $table->timestamps();
        });


        // Orders
        Schema::create('i_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('status')->default(InventoryOrderTrackingStatus::PLACED);
            $table->string('seq')->nullable();
            $table->string('reference')->nullable();
            $table->string('order_year');
            $table->longText('obs')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('i_suppliers');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });


        // Order Detail
        Schema::create('i_order_details', function (Blueprint $table) {
            $table->id();
            $table->integer('qty')->default(1);
            $table->date('expected_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('i_orders');
            $table->foreignId('item_id')->nullable()->constrained('i_items');
            $table->foreignId('warehouse_id')->nullable()->constrained('i_warehouses');
            $table->string('status')->default(InventoryOrderItemStatus::ACCEPTABLE);
            $table->softDeletes();
            $table->timestamps();
        });

        // Deliveries
        Schema::create('i_deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('sales_date')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->softDeletes();
            $table->timestamps();
        });


        // Delivery Detail
        Schema::create('i_delivery_details', function (Blueprint $table) {
            $table->id();
            $table->integer('qty')->default(1);
            $table->date('expected_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->foreignId('delivery_id')->nullable()->constrained('i_deliveries');
            $table->foreignId('item_id')->nullable()->constrained('i_items');
            $table->foreignId('warehouse_id')->nullable()->constrained('i_warehouses');
            $table->softDeletes();
            $table->timestamps();
        });


        // Transfers
        Schema::create('i_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('qty')->default(1);
            $table->date('sent_date')->nullable();
            $table->date('received_date')->nullable();
            $table->longText('obs')->nullable();

            $table->foreignId('item_id')->nullable()->constrained('i_items');
            $table->foreignId('source_id')->nullable()->constrained('i_warehouses');
            $table->foreignId('destination_id')->nullable()->constrained('i_warehouses');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_transfers');
        Schema::dropIfExists('i_delivery_details');
        Schema::dropIfExists('i_deliveries');
        Schema::dropIfExists('i_order_details');
        Schema::dropIfExists('i_orders');
        Schema::dropIfExists('i_suppliers');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('i_warehouses');
        Schema::dropIfExists('i_locations');
        Schema::dropIfExists('i_items');
        Schema::dropIfExists('i_units');
        Schema::dropIfExists('item_categories');
    }
};
