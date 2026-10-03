<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE itransactions ALTER COLUMN qty DROP DEFAULT');
        DB::statement('ALTER TABLE itransactions ALTER COLUMN qty TYPE NUMERIC(18,4) USING qty::numeric(18,4)');
        DB::statement('ALTER TABLE itransactions ALTER COLUMN qty SET DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('itransactions')->exists()) {
            throw new RuntimeException('Cannot remove fractional ledger quantities while inventory transactions are retained.');
        }

        DB::statement('ALTER TABLE itransactions ALTER COLUMN qty DROP DEFAULT');
        DB::statement('ALTER TABLE itransactions ALTER COLUMN qty TYPE VARCHAR(255)');
        DB::statement("ALTER TABLE itransactions ALTER COLUMN qty SET DEFAULT '0'");
    }
};
