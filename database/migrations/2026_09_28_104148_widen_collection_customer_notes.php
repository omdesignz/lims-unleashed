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
        Schema::table('collection_product', function (Blueprint $table): void {
            $table->text('customer_submitted_info')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('collection_product')->whereRaw('CHAR_LENGTH(customer_submitted_info) > 255')->exists()) {
            throw new RuntimeException('Cannot narrow collection customer notes while retained values exceed 255 characters.');
        }

        Schema::table('collection_product', function (Blueprint $table): void {
            $table->string('customer_submitted_info')->nullable()->change();
        });
    }
};
