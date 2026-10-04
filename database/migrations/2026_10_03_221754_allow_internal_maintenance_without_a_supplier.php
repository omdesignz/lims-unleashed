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
        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('maintenance_tasks')->whereNull('supplier_id')->exists()) {
            throw new RuntimeException('Cannot require suppliers while internal maintenance tasks have no supplier.');
        }

        Schema::table('maintenance_tasks', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }
};
