<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every proposal needs its public link token: without it the proposal cannot be sent
 * and its staff page cannot show the customer link. Proposals created outside the
 * authoring action (imports, demo data) may lack one, so each gets a fresh token.
 * Existing tokens are never changed, so links already sent keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE proposals SET unique_hash = gen_random_uuid()::text WHERE unique_hash IS NULL OR unique_hash = \'\'');
    }

    public function down(): void
    {
        // Issued tokens may already have been shared; they are kept.
    }
};
