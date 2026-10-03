<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One live quality certificate per collection product, so repeated or concurrent
 * "generate certificate" requests can never issue two documents for the same sample.
 * Existing duplicates are not resolved automatically: the migration stops and names
 * them, because choosing which issued document survives is a human decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('quality_certificates')
            ->whereNull('deleted_at')
            ->whereNotNull('collection_id')
            ->groupBy('collection_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('collection_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Live quality certificates are duplicated for collection products: '.$duplicates->implode(', ').'. Archive the superseded certificates before migrating.');
        }

        DB::statement('CREATE UNIQUE INDEX quality_certificates_live_collection_unique ON quality_certificates (collection_id) WHERE deleted_at IS NULL AND collection_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS quality_certificates_live_collection_unique');
    }
};
