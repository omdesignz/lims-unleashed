<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Product;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CommercialQuotePickerTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab, bool $authorized = true): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        if ($authorized) {
            $user->givePermissionTo(Permission::findOrCreate('add_quotes', 'web'));
        }

        return $user;
    }

    private function specimen(VAPLab $lab, Warehouse $site, Product $product): LabCode
    {
        $collection = CollectionProduct::create(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'product_id' => $product->id]);
        VAPSampleEntry::factory()->make(['lab_id' => $lab->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'collection_product_id' => $collection->id])->saveQuietly();
        $code = new LabCode(['collection_id' => $collection->id, 'code' => 'PICKER-'.Str::uuid(), 'cl_month' => now()->year, 'codeable_type' => 'analysis']);
        $code->saveQuietly();

        return $code;
    }

    public static function endpoints(): array
    {
        return [['labcodes.getCodeParameters', 'code_id'], ['labcodes.getCodeProducts', 'code_id'], ['labcodes.getWarehouseUninvoicedProducts', 'warehouse_id']];
    }

    #[DataProvider('endpoints')]
    public function test_lookup_requires_commercial_permission_and_valid_ids(string $route, string $parameter): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, false);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route($route, [$parameter => 1]))->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('add_quotes', 'web'));
        $this->getJson(route($route, [$parameter => []]))->assertUnprocessable()->assertJsonValidationErrors($parameter);
        $this->getJson(route($route, [$parameter => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors($parameter);
    }

    public function test_code_and_site_imports_never_return_another_laboratory_specimen(): void
    {
        $lab = VAPLab::factory()->create();
        $peer = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $site = Warehouse::create(['name' => 'Picker site '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared customer'])->id]);
        $matrix = Matrix::create(['description' => 'Picker matrix', 'code' => 'PICK-'.Str::uuid(), 'price' => 25, 'fixed_price' => 20]);
        $product = Product::create(['name' => 'Shared product', 'matrix_id' => $matrix->id]);
        $localCode = $this->specimen($lab, $site, $product);
        $peerCode = $this->specimen($peer, $site, $product);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        foreach (['true', 'false'] as $matrixPricing) {
            foreach (['labcodes.getCodeParameters', 'labcodes.getCodeProducts'] as $route) {
                $this->getJson(route($route, ['code_id' => $peerCode->id, 'use_matrix_price' => $matrixPricing]))->assertNotFound();
                $this->getJson(route($route, ['code_id' => $localCode->id, 'use_matrix_price' => $matrixPricing]))->assertOk();
            }
            $this->getJson(route('labcodes.getWarehouseUninvoicedProducts', ['warehouse_id' => $site->id, 'use_matrix_price' => $matrixPricing]))
                ->assertOk()->assertJsonCount(1)->assertJsonPath('0.itemable_id', $localCode->collection_id);
        }
        $this->assertNotSame($peerCode->collection_id, $localCode->collection_id);
    }
}
