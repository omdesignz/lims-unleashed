<?php

namespace Tests\Feature;

use App\Models\Matrix;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxExemption;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProductCatalogWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_taxed_product_persists_matrix_pricing_and_tax_type(): void
    {
        $admin = $this->verifiedAdmin();
        $matrix = Matrix::query()->create(['description' => 'Taxed product matrix']);
        $taxType = TaxType::query()->create(['name' => 'Test VAT', 'percent' => 14]);
        $name = 'Taxed product '.Str::uuid();

        $response = $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => $name,
                'description' => 'Taxed analytical product.',
                'price' => 1250,
                'fixed_price' => 1500,
                'tax_percentage' => $taxType->percent,
                'matrix_id' => ['value' => $matrix->id, 'label' => $matrix->description],
                'tax_id' => ['value' => $taxType->id, 'label' => $taxType->name],
                'charge_tax' => true,
                'withhold_tax' => false,
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasNoErrors();

        $product = Product::query()->where('name', $name)->firstOrFail();

        $this->assertSame($matrix->id, $product->matrix_id);
        $this->assertSame($taxType->id, $product->tax_id);
        $this->assertSame(1500.0, (float) $product->fixed_price);
        $this->assertTrue($product->charge_tax);
    }

    public function test_exempt_product_is_listed_with_a_human_readable_tax_status(): void
    {
        $admin = $this->verifiedAdmin();
        $matrix = Matrix::query()->create(['description' => 'Exempt product matrix']);
        $exemption = TaxExemption::query()->create(['code' => 'TEST-EXEMPT', 'reason' => 'Test exemption']);
        $name = 'Exempt product '.Str::uuid();

        $response = $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => $name,
                'description' => 'Exempt analytical product.',
                'price' => 800,
                'fixed_price' => 800,
                'tax_percentage' => 0,
                'matrix_id' => ['value' => $matrix->id, 'label' => $matrix->description],
                'exemption_id' => ['value' => $exemption->id, 'label' => $exemption->code],
                'charge_tax' => false,
                'withhold_tax' => false,
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasNoErrors();

        $product = Product::query()->where('name', $name)->firstOrFail();
        $this->assertSame($exemption->id, $product->exemption_id);
        $this->assertFalse($product->charge_tax);

        $this->actingAs($admin)
            ->get(route('products.index', ['search' => $name]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Products/Index')
                ->where('record.data.0.id', $product->id)
                ->where('record.data.0.tax_status', 'Isento'));
    }

    public function test_product_rejects_unknown_tax_and_exemption_references(): void
    {
        $admin = $this->verifiedAdmin();
        $matrix = Matrix::query()->create(['description' => 'Reference validation matrix']);

        $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Invalid tax product '.Str::uuid(),
                'price' => 500,
                'fixed_price' => 500,
                'tax_percentage' => 14,
                'matrix_id' => ['value' => $matrix->id, 'label' => $matrix->description],
                'tax_id' => ['value' => PHP_INT_MAX, 'label' => 'Unknown tax'],
                'charge_tax' => true,
                'withhold_tax' => false,
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('tax_id');

        $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Invalid exemption product '.Str::uuid(),
                'price' => 500,
                'fixed_price' => 500,
                'tax_percentage' => 0,
                'matrix_id' => ['value' => $matrix->id, 'label' => $matrix->description],
                'exemption_id' => ['value' => PHP_INT_MAX, 'label' => 'Unknown exemption'],
                'charge_tax' => false,
                'withhold_tax' => false,
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('exemption_id');
    }
}
