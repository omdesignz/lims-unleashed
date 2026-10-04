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
        Schema::table('i_orders', function (Blueprint $table) {
            $table->jsonb('receipt_history')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('LOCK TABLE i_orders IN ACCESS EXCLUSIVE MODE');
            if (DB::table('i_orders')->whereNotNull('receipt_history')->exists()) {
                throw new RuntimeException('Receipt history must be retained. Use a forward migration once receipts have been recorded.');
            }

            Schema::table('i_orders', function (Blueprint $table) {
                $table->dropColumn('receipt_history');
            });
        });
    }
};
