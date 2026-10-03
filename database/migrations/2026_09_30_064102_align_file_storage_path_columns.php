<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['v_files', 'v_file_versions'] as $table) {
            $columnType = Schema::getColumnType($table, 'content');

            if ($columnType === 'text') {
                continue;
            }

            if ($columnType !== 'bytea') {
                throw new RuntimeException("Unexpected {$table}.content type: {$columnType}.");
            }

            DB::statement("ALTER TABLE {$table} ALTER COLUMN content TYPE text USING convert_from(content, 'UTF8')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('v_files')->exists() || DB::table('v_file_versions')->exists()) {
            throw new RuntimeException('Cannot restore binary file-path columns while documents are retained.');
        }

        foreach (['v_file_versions', 'v_files'] as $table) {
            $columnType = Schema::getColumnType($table, 'content');

            if ($columnType === 'bytea') {
                continue;
            }

            if ($columnType !== 'text') {
                throw new RuntimeException("Unexpected {$table}.content type: {$columnType}.");
            }

            DB::statement("ALTER TABLE {$table} ALTER COLUMN content TYPE bytea USING convert_to(content, 'UTF8')");
        }
    }
};
