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
        DB::table('ratings')
            ->whereNull('rater_type')
            ->whereNotNull('user_id')
            ->update([
                'rater_type' => 'user',
                'rater_id' => DB::raw('user_id'),
                'channel' => 'internal',
            ]);

        DB::table('rating_requests')
            ->whereNull('rater_type')
            ->whereNotNull('user_id')
            ->update([
                'rater_type' => 'user',
                'rater_id' => DB::raw('user_id'),
                'channel' => 'internal',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
