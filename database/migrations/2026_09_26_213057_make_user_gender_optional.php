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
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('ALTER TABLE users ALTER COLUMN gender DROP NOT NULL');

            return;
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('gender', ['M', 'F', 'O'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->whereNull('gender')->exists()) {
            throw new RuntimeException('Cannot require gender while users have not supplied it.');
        }
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('ALTER TABLE users ALTER COLUMN gender SET NOT NULL');

            return;
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('gender', ['M', 'F', 'O'])->nullable(false)->change();
        });
    }
};
