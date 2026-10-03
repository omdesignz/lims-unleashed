<?php

namespace Tests\Feature;

use App\Models\PaidService;
use App\Models\Role;
use App\Models\TaxExemption;
use App\Models\TaxType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaidServiceWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_taxed_service_persists_selected_tax_type(): void
    {
        $admin = $this->verifiedAdmin();
        $taxType = $this->taxType();
        $name = 'Taxed service '.Str::uuid();

        $response = $this->actingAs($admin)
            ->from(route('paidservices.create'))
            ->post(route('paidservices.store'), [
                'name' => $name,
                'description' => 'Tax persistence workflow.',
                'price' => 1250,
                'charge_tax' => true,
                'withhold_tax' => false,
                'tax_id' => [
                    'value' => $taxType->id,
                    'label' => $taxType->name,
                    'percent' => $taxType->percent,
                ],
            ]);

        $response->assertRedirect(route('paidservices.create'));
        $response->assertSessionHasNoErrors();

        $service = PaidService::query()->where('name', $name)->firstOrFail();

        $this->assertSame($taxType->id, $service->tax_id);
        $this->assertSame((float) $taxType->percent, (float) $service->tax_percentage);
        $this->assertSame(1250.0, (float) $service->fixed_price);
        $this->assertTrue($service->charge_tax);
    }

    public function test_service_can_be_updated_without_changing_its_unique_name(): void
    {
        $admin = $this->verifiedAdmin();
        $taxType = $this->taxType();
        $service = PaidService::query()->create([
            'name' => 'Editable service '.Str::uuid(),
            'description' => 'Before update.',
            'price' => 500,
            'fixed_price' => 500,
            'tax_id' => $taxType->id,
            'tax_percentage' => $taxType->percent,
            'charge_tax' => true,
            'withhold_tax' => false,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('paidservices.edit', $service))
            ->put(route('paidservices.update', $service), [
                'name' => $service->name,
                'description' => 'After update.',
                'price' => 725,
                'charge_tax' => true,
                'withhold_tax' => true,
                'tax_id' => [
                    'value' => $taxType->id,
                    'label' => $taxType->name,
                    'percent' => $taxType->percent,
                ],
            ]);

        $response->assertRedirect(route('paidservices.edit', $service));
        $response->assertSessionHasNoErrors();

        $service->refresh();

        $this->assertSame('After update.', $service->description);
        $this->assertSame(725.0, (float) $service->price);
        $this->assertTrue($service->withhold_tax);
    }

    public function test_exempt_service_requires_an_existing_exemption(): void
    {
        $admin = $this->verifiedAdmin();

        $response = $this->actingAs($admin)
            ->from(route('paidservices.create'))
            ->post(route('paidservices.store'), [
                'name' => 'Invalid exempt service '.Str::uuid(),
                'description' => 'Invalid exemption workflow.',
                'price' => 300,
                'charge_tax' => false,
                'withhold_tax' => false,
                'exemption_id' => [
                    'value' => TaxExemption::query()->max('id') + 100000,
                    'label' => 'INVALID',
                ],
            ]);

        $response->assertRedirect(route('paidservices.create'));
        $response->assertSessionHasErrors('exemption_id');
    }

    private function taxType(): TaxType
    {
        return TaxType::query()->create([
            'name' => 'Paid service tax '.Str::uuid(),
            'percent' => 14,
        ]);
    }
}
