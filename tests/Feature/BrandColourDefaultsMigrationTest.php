<?php

namespace Tests\Feature;

use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\LabNetworkAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tests\IsolatedPostgresTestCase;

class BrandColourDefaultsMigrationTest extends IsolatedPostgresTestCase
{
    public function test_legacy_defaults_adopt_the_brand_while_explicit_choices_are_preserved(): void
    {
        $migration = $this->migration();
        $migration->down();
        $legacyNetwork = DB::table('lab_networks')->insertGetId(['name' => 'Legacy default network']);
        $customNetwork = DB::table('lab_networks')->insertGetId(['name' => 'Custom network', 'primary_color' => '#9b2c5c']);
        $this->storeSetting('app_primary_color', '#24664f');
        $this->storeSetting('app_secondary_color', '#0f172a');
        $this->storeSetting('app_accent_color', '#f97316');

        $migration->up();

        $this->assertSame('#0757b5', DB::table('lab_networks')->where('id', $legacyNetwork)->value('primary_color'));
        $this->assertSame('#9b2c5c', DB::table('lab_networks')->where('id', $customNetwork)->value('primary_color'));
        $this->assertSame('"#0757b5"', $this->storedSetting('app_primary_color'));
        $this->assertSame('"#061f46"', $this->storedSetting('app_secondary_color'));
        $this->assertSame('"#f97316"', $this->storedSetting('app_accent_color'));
    }

    public function test_new_networks_default_to_the_brand_colour_and_rollback_restores_only_the_default(): void
    {
        $migration = $this->migration();
        $brandNetwork = DB::table('lab_networks')->insertGetId(['name' => 'Brand default network']);
        $this->assertSame('#0757b5', DB::table('lab_networks')->where('id', $brandNetwork)->value('primary_color'));

        $migration->down();

        $this->assertSame('#0757b5', DB::table('lab_networks')->where('id', $brandNetwork)->value('primary_color'));
        $rolledBack = DB::table('lab_networks')->insertGetId(['name' => 'Rolled back network']);
        $this->assertSame('#24664f', DB::table('lab_networks')->where('id', $rolledBack)->value('primary_color'));
        $migration->up();
        $this->assertSame('#0757b5', DB::table('lab_networks')->where('id', $rolledBack)->value('primary_color'));
    }

    public function test_laboratory_context_inherits_the_network_colour_before_any_override(): void
    {
        $network = LabNetwork::factory()->create();
        $lab = VAPLab::factory()->create(['network_id' => $network->id, 'primary_color' => null]);
        $user = User::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);

        $context = app(LabNetworkAccess::class)->context($user->fresh(), $lab->id);

        $this->assertSame($network->fresh()->primary_color, $context['active_lab']['primary_color']);
        $this->assertTrue($context['active_lab']['inherited_color']);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_03_100504_adopt_vap_brand_colour_defaults.php');
    }

    private function storeSetting(string $name, string $colour): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => $name],
            ['payload' => json_encode($colour), 'locked' => false, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    private function storedSetting(string $name): ?string
    {
        return DB::table('settings')->where('group', 'general')->where('name', $name)->value('payload');
    }
}
