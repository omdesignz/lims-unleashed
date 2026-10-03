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
        if (Schema::hasIndex('sample_entries', 'sample_entries_sample_year_seq_unique')) {
            Schema::table('sample_entries', function (Blueprint $table): void {
                $table->dropUnique('sample_entries_sample_year_seq_unique');
            });
        }

        if (! Schema::hasIndex('sample_entries', 'sample_entries_lab_year_seq_unique')) {
            Schema::table('sample_entries', function (Blueprint $table): void {
                $table->unique(['lab_id', 'sample_year', 'seq'], 'sample_entries_lab_year_seq_unique');
            });
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS sample_entries_unassigned_year_seq_unique ON sample_entries (sample_year, seq) WHERE lab_id IS NULL AND sample_year IS NOT NULL AND seq IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasOverlappingNumbers = DB::table('sample_entries')
            ->whereNotNull('sample_year')
            ->whereNotNull('seq')
            ->select(['sample_year', 'seq'])
            ->groupBy(['sample_year', 'seq'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasOverlappingNumbers) {
            throw new LogicException('Cannot restore global sample numbering after laboratories have issued overlapping numbers.');
        }

        DB::statement('DROP INDEX IF EXISTS sample_entries_unassigned_year_seq_unique');

        if (Schema::hasIndex('sample_entries', 'sample_entries_lab_year_seq_unique')) {
            Schema::table('sample_entries', function (Blueprint $table): void {
                $table->dropUnique('sample_entries_lab_year_seq_unique');
            });
        }

        if (! Schema::hasIndex('sample_entries', 'sample_entries_sample_year_seq_unique')) {
            Schema::table('sample_entries', function (Blueprint $table): void {
                $table->unique(['sample_year', 'seq'], 'sample_entries_sample_year_seq_unique');
            });
        }
    }
};
