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
        Schema::table('i_suppliers', function (Blueprint $table): void {
            $table->string('currency', 3)->nullable();
        });

        Schema::table('i_orders', function (Blueprint $table): void {
            $table->string('currency', 3)->nullable();
            $table->decimal('total_amount', 20, 4)->default(0);
        });

        Schema::table('i_order_details', function (Blueprint $table): void {
            $table->integer('received_qty')->default(0);
            $table->string('currency', 3)->nullable();
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('total_price', 20, 4)->storedAs('qty * unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('i_suppliers')->exists()
            || DB::table('i_orders')->exists()
            || DB::table('i_order_details')->exists()) {
            throw new RuntimeException('Cannot remove procurement pricing from retained records.');
        }

        Schema::table('i_order_details', function (Blueprint $table): void {
            $table->dropColumn('total_price');
        });

        Schema::table('i_order_details', function (Blueprint $table): void {
            $table->dropColumn(['received_qty', 'currency', 'unit_price']);
        });

        Schema::table('i_orders', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'total_amount']);
        });

        Schema::table('i_suppliers', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
