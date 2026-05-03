<?php

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
        Schema::table('reagent_consumption', function (Blueprint $table) {
            if (! Schema::hasColumn('reagent_consumption', 'inventory_transaction_id')) {
                $table->foreignId('inventory_transaction_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('itransactions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reagent_consumption', function (Blueprint $table) {
            if (Schema::hasColumn('reagent_consumption', 'inventory_transaction_id')) {
                $table->dropConstrainedForeignId('inventory_transaction_id');
            }
        });
    }
};
