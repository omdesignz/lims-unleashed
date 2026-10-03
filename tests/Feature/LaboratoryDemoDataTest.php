<?php

namespace Tests\Feature;

use App\Actions\CreateLaboratoryDemoData;
use App\Models\Analysis;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabNetwork;
use App\Models\Product;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Services\LaboratoryWorkflowOwnership;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LaboratoryDemoDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_demo_access_uses_real_fortify_login_and_canonical_local_graphs(): void
    {
        $demo = app(CreateLaboratoryDemoData::class)->execute();
        $this->assertSame(CreateLaboratoryDemoData::EMAIL, $demo['user']->email);
        $this->assertTrue($demo['user']->is_active);
        $this->assertNotNull($demo['user']->email_verified_at);
        $this->assertFalse($demo['user']->hasRole('admin'));
        $this->assertTrue(Hash::check($demo['password'], $demo['user']->password));
        $this->assertGreaterThanOrEqual(32, strlen($demo['password']));
        $this->assertCount(4, $demo['entries']);
        $this->assertSame($demo['main_lab']->id, $demo['main_lab']->network->main_lab_id);
        $ownership = app(LaboratoryWorkflowOwnership::class);

        foreach ($demo['entries'] as $entry) {
            $this->assertStringContainsString('-L'.$entry->lab_id.'-', $entry->code);
            $this->assertSame('POR_INICIAR', $entry->status);
            $this->assertNull($entry->proposal_id);
            $this->assertSame(CreateLaboratoryDemoData::MARKER, data_get($entry->client_submitted_info, 'demo_dataset'));
            $type = data_get($entry->client_submitted_info, 'collection_type');
            $record = $ownership->collectionAccessionsForLaboratory($entry->lab_id, $type)->findOrFail($entry->collection_product_id);
            $this->assertSame($entry->customer_id, $record->customer_id);
            $this->assertSame($entry->warehouse_id, $record->warehouse_id);
            $this->assertSame($entry->packaging_id, $record->pack_id);
            $this->assertSame($type, $record->collection->collectionable_type);
            $this->assertSame(2, $record->code->samples()->count());
            $this->assertSame(2, Analysis::query()->where('cl_id', $record->code->id)->count());
            $this->assertSame($demo['user']->id, $record->owner_id);
            $this->assertFalse($ownership->collectionProductsForLaboratory(
                $entry->lab_id === $demo['main_lab']->id ? $demo['peer_lab']->id : $demo['main_lab']->id
            )->whereKey($record->id)->exists());
        }

        $proposals = VAPProposal::query()->whereIn('lab_id', [$demo['main_lab']->id, $demo['peer_lab']->id])->get();
        $this->assertCount(2, $proposals);
        $this->assertCount(1, $proposals->pluck('customer_id')->unique());

        foreach ($proposals as $proposal) {
            $this->assertSame('PENDING', $proposal->status);
            $this->assertEquals(12000, $proposal->total);
            $this->assertEquals($proposal->total, $proposal->items->sum('total'));
        }

        $this->post(route('login'), ['email' => $demo['user']->email, 'password' => $demo['password']])
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($demo['user']);
        $this->withSession(['active_lab_id' => $demo['main_lab']->id])
            ->get(route('directcollections.index'))->assertOk();
        $this->get(route('programmedcollections.index'))->assertOk();
    }

    public function test_repeated_demo_setup_preserves_password_records_dates_and_sequences(): void
    {
        $first = app(CreateLaboratoryDemoData::class)->execute();
        $snapshot = $this->snapshot();
        $this->travel(1)->days();
        $second = app(CreateLaboratoryDemoData::class)->execute();

        $this->assertNull($second['password']);
        $this->assertSame($first['user']->id, $second['user']->id);
        $this->assertSame($first['main_lab']->id, $second['main_lab']->id);
        $this->assertSame($first['peer_lab']->id, $second['peer_lab']->id);
        $this->assertSame($first['user']->password, $second['user']->password);
        $this->assertEquals($snapshot, $this->snapshot());
    }

    public function test_existing_administrator_lab_and_settings_remain_unchanged(): void
    {
        $administrator = User::factory()->create(['email' => 'vmanuel@example.test']);
        $lab = VAPLab::factory()->create(['name' => 'Existing lab']);
        $userBefore = $administrator->fresh()->getAttributes();
        $labBefore = $lab->fresh()->getAttributes();
        $settings = app(GeneralSettings::class)->toArray();

        app(CreateLaboratoryDemoData::class)->execute();

        $this->assertSame($userBefore, $administrator->fresh()->getAttributes());
        $this->assertSame($labBefore, $lab->fresh()->getAttributes());
        $this->assertSame($settings, app(GeneralSettings::class)->toArray());
        $this->assertDatabaseMissing('lab_user', ['user_id' => $administrator->id]);
    }

    public function test_demo_setup_does_not_send_notifications_mail_or_jobs(): void
    {
        Notification::fake();
        Mail::fake();
        Bus::fake();

        app(CreateLaboratoryDemoData::class)->execute();

        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
        Bus::assertNothingDispatched();
    }

    public function test_repeated_setup_does_not_reactivate_a_disabled_demo_account(): void
    {
        $demo = app(CreateLaboratoryDemoData::class)->execute();
        $demo['user']->update(['is_active' => false]);
        $before = $this->snapshot();

        try {
            app(CreateLaboratoryDemoData::class)->execute();
            $this->fail('Demo setup must not undo an account revocation.');
        } catch (LogicException) {
            $this->assertEquals($before, $this->snapshot());
            $this->assertFalse($demo['user']->fresh()->is_active);
        }
    }

    public function test_repeated_setup_does_not_restore_removed_demo_membership(): void
    {
        $demo = app(CreateLaboratoryDemoData::class)->execute();
        DB::table('lab_user')->where('user_id', $demo['user']->id)->where('lab_id', $demo['peer_lab']->id)->delete();
        $before = $this->snapshot();

        try {
            app(CreateLaboratoryDemoData::class)->execute();
            $this->fail('Demo setup must not undo a membership revocation.');
        } catch (LogicException) {
            $this->assertEquals($before, $this->snapshot());
        }
    }

    #[DataProvider('nonDemoEnvironments')]
    public function test_demo_setup_refuses_non_local_environments_before_writes(string $environment): void
    {
        $before = $this->snapshot();
        $this->app->detectEnvironment(fn (): string => $environment);

        try {
            $this->artisan('app:demo-laboratory')->expectsOutput('Demo data is limited to local and testing environments.')->assertFailed();
            $this->assertEquals($before, $this->snapshot());
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }
    }

    /** @return array<string, array{string}> */
    public static function nonDemoEnvironments(): array
    {
        return ['production' => ['production'], 'staging' => ['staging']];
    }

    #[DataProvider('collidingIdentifiers')]
    public function test_demo_setup_never_adopts_existing_or_archived_identifiers(string $collision): void
    {
        match ($collision) {
            'account' => User::factory()->create(['email' => CreateLaboratoryDemoData::EMAIL]),
            'archived_account' => User::factory()->create(['email' => CreateLaboratoryDemoData::EMAIL, 'deleted_at' => now()]),
            'network' => LabNetwork::factory()->create(['name' => CreateLaboratoryDemoData::NETWORK_NAME]),
            'catalogue' => Product::query()->create(['name' => '[DEMO] Existing unrelated product']),
            'catalogue_code' => Department::factory()->create(['name' => 'Unrelated department', 'code' => 'DEMO-FQ']),
            'archived_customer' => tap(Customer::query()->create(['name' => '[DEMO] Existing unrelated customer']), fn (Customer $customer): bool => $customer->delete()),
        };
        $before = $this->snapshot();

        try {
            app(CreateLaboratoryDemoData::class)->execute();
            $this->fail('Occupied demo identifiers must not be adopted or overwritten.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('No existing record was modified', $exception->getMessage());
            $this->assertEquals($before, $this->snapshot());
        }
    }

    /** @return array<string, array{string}> */
    public static function collidingIdentifiers(): array
    {
        return collect(['account', 'archived_account', 'network', 'catalogue', 'catalogue_code', 'archived_customer'])
            ->mapWithKeys(fn (string $collision): array => [$collision => [$collision]])->all();
    }

    public function test_failed_intake_rolls_back_accounts_memberships_catalogue_and_sequences(): void
    {
        $before = $this->snapshot();
        $created = 0;
        Analysis::creating(function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Simulated demo analysis failure.');
            }
        });

        try {
            app(CreateLaboratoryDemoData::class)->execute();
            $this->fail('Expected simulated intake failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated demo analysis failure.', $exception->getMessage());
        }

        $this->assertEquals($before, $this->snapshot());
    }

    public function test_command_displays_generated_password_only_on_initial_creation(): void
    {
        $this->assertSame(0, Artisan::call('app:demo-laboratory'));
        $initialOutput = Artisan::output();
        $this->assertMatchesRegularExpression('/Password: ([a-zA-Z0-9]{32})/', $initialOutput);
        $this->assertStringContainsString(CreateLaboratoryDemoData::EMAIL, $initialOutput);
        $hash = User::query()->where('email', CreateLaboratoryDemoData::EMAIL)->sole()->password;

        $this->assertSame(0, Artisan::call('app:demo-laboratory'));
        $this->assertStringContainsString('Password unchanged.', Artisan::output());
        $this->assertStringNotContainsString('Password:', Artisan::output());
        $this->assertSame($hash, User::query()->where('email', CreateLaboratoryDemoData::EMAIL)->sole()->password);
    }

    /** @return array<string, array<int, object>> */
    private function snapshot(): array
    {
        return collect([
            'users', 'lab_networks', 'labs', 'lab_user', 'roles', 'permissions', 'model_has_roles', 'role_has_permissions',
            'departments', 'department_user', 'customers', 'warehouses', 'matrixes', 'matrix_profile', 'profiles',
            'parameters', 'parameter_profile', 'products', 'units', 'packaging_categories', 'collection_end_results',
            'proposals', 'proposal_templates', 'proposal_items', 'sample_entries', 'collections', 'direct_collections', 'programmed_collections',
            'collection_product', 'lab_codes', 'samples', 'analysis', 'sequence_counters', 'activity_log',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->get()
            ->sortBy(fn (object $record): string => (string) json_encode($record))->values()->all()])->all();
    }
}
