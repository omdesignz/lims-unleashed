<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Earlier defaults that were never an explicit choice, mapped to the VAP brand.
     *
     * @var array<string, array{legacy: list<string>, brand: string}>
     */
    private const SETTINGS = [
        'app_primary_color' => ['legacy' => ['#24664f', '#143d37'], 'brand' => '#0757b5'],
        'app_secondary_color' => ['legacy' => ['#0f172a', '#07110f'], 'brand' => '#061f46'],
        'app_accent_color' => ['legacy' => ['#14b8a6', '#d9b05f'], 'brand' => '#087cf0'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE lab_networks ALTER COLUMN primary_color SET DEFAULT '#0757b5'");
        DB::table('lab_networks')->where('primary_color', '#24664f')->update(['primary_color' => '#0757b5']);

        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach (self::SETTINGS as $name => $colours) {
            DB::table('settings')
                ->where('group', 'general')
                ->where('name', $name)
                ->whereIn(DB::raw('payload::text'), array_map(fn (string $colour): string => json_encode($colour), $colours['legacy']))
                ->update(['payload' => json_encode($colours['brand'])]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Only the column default is restored: once converted, a brand colour is
     * indistinguishable from one an administrator chose on purpose.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE lab_networks ALTER COLUMN primary_color SET DEFAULT '#24664f'");
    }
};
