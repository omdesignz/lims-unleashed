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
        Schema::table('itransactions', function (Blueprint $table): void {
            $table->foreignId('batch_id')->nullable()->constrained('i_inventory_batches')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
        });

        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('i_warehouses')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('i_inventory_batches')->nullOnDelete();
            $table->string('usage_type')->nullable();
            $table->string('project')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reagent_consumption', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['usage_type', 'project']);
        });

        Schema::table('itransactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn(['reason', 'notes']);
        });
    }
};
