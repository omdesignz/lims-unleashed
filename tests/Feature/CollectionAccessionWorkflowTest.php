<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionAccessionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_direct_collection_quality_context_is_exposed_and_persisted(): void
    {
        $user = $this->verifiedAdmin();
        $record = $this->collectionProduct('direct', requiresPackaging: true);

        $record->forceFill([
            'sample_status' => 'Recebida',
            'sampling_plan_ref' => 'PLANO-DIRETO-01',
            'customer_submitted_info' => 'Informação inicial',
        ])->save();

        $this->actingAs($user)
            ->get(route('directcollections.edit', ['collection' => $record->id]))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DirectCollections/Edit')
                ->where('record.sample_status', 'Recebida')
                ->where('record.sampling_plan_ref', 'PLANO-DIRETO-01')
                ->where('record.customer_submitted_info', 'Informação inicial'));

        $payload = $this->updatePayload($record, [
            'sample_status' => 'Aceite para análise',
            'sampling_plan_ref' => 'PLANO-DIRETO-02',
            'customer_submitted_info' => 'Informação revista',
        ]);

        $this->actingAs($user)
            ->put(route('directcollections.update', ['collection' => $record->id]), $payload)
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Aceite para análise', $record->sample_status);
        $this->assertSame('PLANO-DIRETO-02', $record->sampling_plan_ref);
        $this->assertSame('Informação revista', $record->customer_submitted_info);
    }

    public function test_programmed_collection_quality_context_is_exposed_and_persisted(): void
    {
        $user = $this->verifiedAdmin();
        $record = $this->collectionProduct('programmed');

        $record->forceFill([
            'sample_status' => 'Programada',
            'sampling_plan_ref' => 'PLANO-PROGRAMADO-01',
            'customer_submitted_info' => 'Condições iniciais',
        ])->save();

        $this->actingAs($user)
            ->get(route('programmedcollections.edit', ['collection' => $record->id]))
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgrammedCollections/Edit')
                ->where('record.sample_status', 'Programada')
                ->where('record.sampling_plan_ref', 'PLANO-PROGRAMADO-01')
                ->where('record.customer_submitted_info', 'Condições iniciais'));

        $payload = $this->updatePayload($record, [
            'sample_status' => 'Colhida',
            'sampling_plan_ref' => 'PLANO-PROGRAMADO-02',
            'customer_submitted_info' => 'Condições confirmadas',
            'collection_location' => 'Linha de produção 2',
            'vehicle_reference' => null,
        ]);

        $this->actingAs($user)
            ->put(route('programmedcollections.update', ['collection' => $record->id]), $payload)
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Colhida', $record->sample_status);
        $this->assertSame('PLANO-PROGRAMADO-02', $record->sampling_plan_ref);
        $this->assertSame('Condições confirmadas', $record->customer_submitted_info);
    }

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for collection accession testing.');

        return $admin;
    }

    private function collectionProduct(string $type, bool $requiresPackaging = false): CollectionProduct
    {
        return CollectionProduct::query()
            ->whereRelation('collection', 'collectionable_type', $type)
            ->whereNotNull(['customer_id', 'warehouse_id', 'product_id', 'result_id', 'collection_id'])
            ->when($requiresPackaging, fn ($query) => $query->whereNotNull('pack_id'))
            ->with(['collection.collaborations', 'collection.reasons'])
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(CollectionProduct $record, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->option($record->customer_id),
            'warehouse_id' => $this->option($record->warehouse_id),
            'collaborations' => $record->collection->collaborations
                ->map(fn ($collaboration): array => $this->option($collaboration->id, $collaboration->name))
                ->values()
                ->all(),
            'collectionreasons' => $record->collection->reasons
                ->map(fn ($reason): array => $this->option($reason->id, $reason->name))
                ->values()
                ->all(),
            'collection_date' => now()->toDateString(),
            'collection_id' => $record->collection_id,
            'product_id' => $this->option($record->product_id),
            'temperature_id' => $this->nullableOption($record->temperature_id),
            'vehicle_id' => $this->nullableOption($record->vehicle_id),
            'owner_id' => $this->nullableOption($record->owner_id),
            'result_id' => $this->option($record->result_id),
            'pack_id' => $this->nullableOption($record->pack_id),
            'invoice_id' => $this->nullableOption($record->invoice_id),
            'comercial_brand' => $record->comercial_brand,
            'du_no' => $record->du_no,
            'origin' => $record->origin,
            'location' => $record->location,
            'term_no' => $record->term_no,
            'container_no' => $record->container_no,
            'recollection' => (bool) $record->recollection,
            'obs' => $record->obs,
            'sample_status' => $record->sample_status,
            'sampling_plan_ref' => $record->sampling_plan_ref,
            'customer_submitted_info' => $record->customer_submitted_info,
            'processed' => (bool) $record->processed,
            'collected_by_lab' => (bool) $record->collected_by_lab,
            'expiry_date' => $this->dateValue($record->expiry_date),
            'production_date' => $this->dateValue($record->production_date),
            'qty' => $record->qty ?: '1',
            'collected_qty' => $record->collected_qty ?: '1',
            'lot' => $record->lot,
            'bl' => $record->bl,
            'temperature_value' => $record->temperature_value,
            'invoiced' => (bool) $record->invoiced,
            'status' => (bool) $record->status,
        ], $overrides);
    }

    /**
     * @return array{value: int, label: string}
     */
    private function option(int $value, ?string $label = null): array
    {
        return [
            'value' => $value,
            'label' => $label ?? (string) $value,
        ];
    }

    /**
     * @return array{value: int, label: string}|null
     */
    private function nullableOption(?int $value): ?array
    {
        return $value ? $this->option($value) : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 10);
    }
}
