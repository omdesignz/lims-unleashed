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
        foreach ([config('permission.table_names.roles'), config('permission.table_names.permissions')] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('label')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([config('permission.table_names.roles'), config('permission.table_names.permissions')] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('label');
            });
        }
    }
};
