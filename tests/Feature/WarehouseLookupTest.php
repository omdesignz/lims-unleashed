<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WarehouseLookupTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
    }

    public function test_site_picker_exposes_only_public_selection_fields(): void
    {
        $customer = Customer::query()->create(['name' => 'Lookup customer']);
        $site = Warehouse::query()->create(['customer_id' => $customer->id, 'name' => 'Named site', 'address' => 'Lookup address',
            'code' => 'LOOKUP-PRIVATE', 'email' => 'lookup@example.test', 'focal_point_contact' => 'Private contact']);
        $site->forceFill(['password' => 'fixture-only-password-hash', 'remember_token' => 'fixture-only-remember-token'])->saveQuietly();

        $this->getJson(route('warehouses.getWarehouse', ['customer_id' => $customer->id]))
            ->assertOk()->assertExactJson([['id' => $site->id, 'customer_id' => $customer->id,
                'name' => 'Named site', 'address' => 'Lookup address', 'code' => 'LOOKUP-PRIVATE']]);
    }

    public function test_missing_customer_returns_no_sites(): void
    {
        $site = Warehouse::query()->create(['customer_id' => Customer::query()->create(['name' => 'Lookup customer'])->id, 'name' => 'Site']);
        $this->getJson(route('warehouses.getWarehouse'))->assertOk()->assertExactJson([]);
        $this->getJson(route('warehouses.getWarehouse', ['customer_id' => '']))->assertOk()->assertExactJson([]);
        $this->assertModelExists($site);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidCustomers(): array
    {
        return ['undefined' => ['undefined'], 'array' => [[1]], 'zero' => [0], 'negative' => [-1]];
    }

    #[DataProvider('invalidCustomers')]
    public function test_invalid_customer_ids_are_validation_errors_not_database_errors(mixed $customer): void
    {
        $this->getJson(route('warehouses.getWarehouse', ['customer_id' => $customer]))
            ->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_search_is_customer_scoped_and_excludes_archived_sites_and_customers(): void
    {
        $customer = Customer::query()->create(['name' => 'Lookup customer']);
        $site = Warehouse::query()->create(['customer_id' => $customer->id, 'name' => 'Unique lookup name']);
        Warehouse::query()->create(['customer_id' => Customer::query()->create(['name' => 'Other customer'])->id, 'name' => 'Unique lookup name elsewhere']);
        $archived = Warehouse::query()->create(['customer_id' => $customer->id, 'name' => 'Unique lookup name archived']);
        $archived->delete();

        $this->getJson(route('warehouses.getWarehouse', ['customer_id' => $customer->id, 'q' => 'Unique lookup']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $site->id);
        $customer->delete();
        $this->getJson(route('warehouses.getWarehouse', ['customer_id' => $customer->id]))->assertOk()->assertExactJson([]);
    }

    public function test_invalid_search_shape_is_rejected(): void
    {
        foreach ([[1], str_repeat('x', 201)] as $query) {
            $this->getJson(route('warehouses.getWarehouse', ['q' => $query]))->assertUnprocessable()->assertJsonValidationErrors('q');
        }
    }
}
