<?php

namespace Tests\Feature;

use App\Models\ContractGuide;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ContractGuideWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    /**
     * @return array{customer: Customer, warehouse: Warehouse, product: Product, country: Country}
     */
    private function referenceData(): array
    {
        $customer = Customer::query()->create(['name' => 'Contract guide customer '.fake()->uuid()]);
        $warehouse = Warehouse::query()->create([
            'customer_id' => $customer->id,
            'name' => 'Contract guide site '.fake()->uuid(),
            'address' => 'Luanda',
        ]);

        return [
            'customer' => $customer,
            'warehouse' => $warehouse,
            'product' => Product::query()->create(['name' => 'Contract guide product '.fake()->uuid()]),
            'country' => Country::query()->create(['name' => 'Angola', 'code' => 'AO', 'phone_code' => '244']),
        ];
    }

    public function test_verified_admin_can_create_a_contract_guide_with_traceable_items(): void
    {
        $admin = $this->verifiedAdmin();
        $data = $this->referenceData();
        $reference = 'CODEX-GC-'.uniqid();

        $response = $this->actingAs($admin)->post(route('contractguides.store'), [
            'customer_id' => ['value' => $data['customer']->id, 'label' => $data['customer']->name],
            'warehouse_id' => ['value' => $data['warehouse']->id, 'label' => $data['warehouse']->address],
            'collection_id' => null,
            'ref_no' => $reference,
            'entry_point' => 'Terminal de ensaio',
            'collection_point' => 'Armazém central',
            'du_no' => 'DU-001',
            'nif' => '5000000000',
            'contact' => 'Responsável técnico',
            'email' => 'quality@example.test',
            'bl' => 'BL-001',
            'obs' => 'Registo criado pelo teste de fluxo.',
            'date' => now()->toDateString(),
            'items' => [
                $this->itemPayload($data['product'], $data['country'], 'Lote inicial'),
            ],
        ]);

        $guide = ContractGuide::query()->where('ref_no', $reference)->firstOrFail();

        $response->assertRedirect(route('contractguides.edit', $guide));
        $this->assertNotEmpty($guide->guide_no);
        $this->assertSame($data['customer']->id, $guide->customer_id);
        $this->assertSame($data['warehouse']->id, $guide->warehouse_id);
        $this->assertDatabaseHas('contract_guide_items', [
            'guide_id' => $guide->id,
            'product_id' => $data['product']->id,
            'country_id' => $data['country']->id,
            'manufacturer' => 'Fabricante validado',
            'brand' => 'Marca controlada',
            'du_no' => 'DU-001',
        ]);
    }

    public function test_update_synchronizes_existing_new_and_removed_guide_items_atomically(): void
    {
        $admin = $this->verifiedAdmin();
        $data = $this->referenceData();
        $guide = ContractGuide::query()->create([
            'user_id' => $admin->id,
            'customer_id' => $data['customer']->id,
            'warehouse_id' => $data['warehouse']->id,
            'guide_month' => now()->format('Y'),
            'ref_no' => 'Antes da atualização',
            'date' => now()->toDateString(),
        ]);
        $retainedItem = $guide->items()->create($this->normalizedItemData($data['product'], $data['country'], 'Item original'));
        $removedItem = $guide->items()->create($this->normalizedItemData($data['product'], $data['country'], 'Item removido'));

        $response = $this->actingAs($admin)->put(route('contractguides.update', $guide), [
            'customer_id' => ['value' => $data['customer']->id, 'label' => $data['customer']->name],
            'warehouse_id' => ['value' => $data['warehouse']->id, 'label' => $data['warehouse']->address],
            'collection_id' => null,
            'ref_no' => 'Depois da atualização',
            'date' => now()->toDateString(),
            'items' => [
                array_merge($this->itemPayload($data['product'], $data['country'], 'Item revisto'), [
                    'id' => $retainedItem->id,
                    'guide_id' => $guide->id,
                    'manufacturer' => 'Fabricante atualizado',
                ]),
                $this->itemPayload($data['product'], $data['country'], 'Novo item'),
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contract_guides', [
            'id' => $guide->id,
            'ref_no' => 'Depois da atualização',
        ]);
        $this->assertDatabaseHas('contract_guide_items', [
            'id' => $retainedItem->id,
            'manufacturer' => 'Fabricante atualizado',
        ]);
        $this->assertSoftDeleted('contract_guide_items', ['id' => $removedItem->id]);
        $this->assertSame(2, $guide->items()->count());
    }

    public function test_customs_reference_schema_is_repeatable_and_refuses_lossy_rollback(): void
    {
        $data = $this->referenceData();
        $guide = ContractGuide::query()->create(['guide_month' => now()->format('Y')]);
        $item = $guide->items()->create($this->normalizedItemData($data['product'], $data['country'], 'Retained customs evidence'));
        $migration = require database_path('migrations/2026_09_28_163321_add_customs_reference_to_contract_guide_items_table.php');
        $migration->up();
        $this->assertSame('DU-OLD', $item->fresh()->du_no);

        try {
            $migration->down();
            $this->fail('Rollback must not discard retained customs references.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained contract guide items', $exception->getMessage());
            $this->assertTrue(Schema::hasColumn('contract_guide_items', 'du_no'));
            $this->assertSame('DU-OLD', $item->fresh()->du_no);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(Product $product, Country $country, string $observations): array
    {
        return [
            'product_id' => ['value' => $product->id, 'label' => $product->name],
            'country_id' => ['value' => $country->id, 'label' => $country->name],
            'manufacturer' => 'Fabricante validado',
            'brand' => 'Marca controlada',
            'lot' => 'LOT-001',
            'bl' => 'BL-001',
            'du_no' => 'DU-001',
            'collection_id' => null,
            'date' => now()->toDateString(),
            'obs' => $observations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedItemData(Product $product, Country $country, string $observations): array
    {
        return [
            'product_id' => $product->id,
            'country_id' => $country->id,
            'manufacturer' => 'Fabricante original',
            'origin' => $country->name,
            'brand' => 'Marca original',
            'lot' => 'LOT-OLD',
            'bl' => 'BL-OLD',
            'du_no' => 'DU-OLD',
            'date' => now()->toDateString(),
            'obs' => $observations,
        ];
    }
}
