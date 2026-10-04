<?php

namespace Tests\Feature;

use App\Actions\ConvertInventoryNeedToOrder;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryNeedWorkflowIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('decidedStates')]
    public function test_decisions_cannot_overwrite_a_need_that_is_no_longer_submitted(string $status): void
    {
        [, $need] = $this->fixture($status);
        $before = $need->getRawOriginal();
        $line = $need->items()->firstOrFail();
        $lineBefore = $line->getRawOriginal();
        $this->post(route('vap-inventory.needs.approve', $need), [
            'items' => [['id' => $line->id, 'quantity_approved' => '0.2500']],
        ])->assertSessionHasErrors('need');
        $this->post(route('vap-inventory.needs.reject', $need), [
            'approval_notes' => 'Overwrite an existing decision',
        ])->assertSessionHasErrors('need');
        $this->assertSame($before, $need->fresh()->getRawOriginal());
        $this->assertSame($lineBefore, $line->fresh()->getRawOriginal());
        Notification::assertNothingSent();
    }

    public static function decidedStates(): array
    {
        return array_map(fn (string $status): array => [$status], [
            'draft', 'approved', 'rejected', 'ordered', 'partially_fulfilled', 'fulfilled',
        ]);
    }

    public function test_approval_requires_a_decision_for_every_line_without_partial_writes(): void
    {
        [, $need] = $this->fixture();
        $first = $need->items()->firstOrFail();
        $second = $first->replicate();
        $second->save();
        $this->post(route('vap-inventory.needs.approve', $need), [
            'items' => [['id' => $first->id, 'quantity_approved' => '0.2500']],
        ])->assertSessionHasErrors('items');
        $this->assertSame('submitted', $need->fresh()->status);
        $this->assertNull($first->fresh()->quantity_approved);
        $this->assertNull($second->fresh()->quantity_approved);
        Notification::assertNothingSent();
    }

    public function test_conversion_rejects_unapproved_lines_instead_of_ordering_the_requested_quantity(): void
    {
        [, $need, $supplier] = $this->fixture('approved');
        $need->items()->firstOrFail()->update(['quantity_approved' => null, 'status' => 'requested']);
        $this->post(route('vap-inventory.needs.convert-to-order', $need), $this->conversion($supplier))
            ->assertSessionHasErrors('items');
        $this->assertNull($need->fresh()->inventory_order_id);
        $this->assertSame(0, InventoryOrder::where('supplier_id', $supplier->id)->count());
        Notification::assertNothingSent();
    }

    public function test_conversion_reloads_stale_inputs_and_cannot_create_a_second_order(): void
    {
        [$user, $need, $supplier] = $this->fixture('approved');
        $data = $this->conversion($supplier);
        $action = app(ConvertInventoryNeedToOrder::class);
        $order = $action->execute($user->id, $need->lab_id, $need->id, $data);
        try {
            $action->execute($user->id, $need->lab_id, $need->id, $data);
            $this->fail('A stale request created another purchase order.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('need', $exception->errors());
        }
        $this->assertSame($order->id, $need->fresh()->inventory_order_id);
        $this->assertSame(1, InventoryOrder::where('supplier_id', $supplier->id)->count());
        $this->assertSame('0.2500', $order->items()->sole()->qty);
    }

    public function test_conversion_handles_an_unavailable_approver_without_a_post_commit_error(): void
    {
        [, $need, $supplier] = $this->fixture('approved');
        $this->assertNull($need->approved_by_id);
        $this->post(route('vap-inventory.needs.convert-to-order', $need), $this->conversion($supplier))
            ->assertRedirect();
        $this->assertSame('ordered', $need->fresh()->status);
        $this->assertSame(1, InventoryOrder::where('supplier_id', $supplier->id)->count());
    }

    public function test_invalid_later_line_rolls_back_earlier_approval(): void
    {
        [, $need] = $this->fixture();
        $first = $need->items()->firstOrFail();
        $second = $first->replicate();
        $second->save();
        $this->post(route('vap-inventory.needs.approve', $need), [
            'items' => [
                ['id' => $first->id, 'quantity_approved' => '0.2500'],
                ['id' => $second->id, 'quantity_approved' => '0.5001'],
            ],
        ])->assertSessionHasErrors('items');
        $this->assertNull($first->fresh()->quantity_approved);
        $this->assertNull($second->fresh()->quantity_approved);
        $this->assertSame('submitted', $need->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_conversion_rejects_a_material_archived_since_approval(): void
    {
        [, $need, $supplier] = $this->fixture('approved');
        $need->items()->firstOrFail()->inventoryItem->delete();
        $this->post(route('vap-inventory.needs.convert-to-order', $need), $this->conversion($supplier))
            ->assertSessionHasErrors('items');
        $this->assertNull($need->fresh()->inventory_order_id);
        $this->assertSame(0, InventoryOrder::where('supplier_id', $supplier->id)->count());
        Notification::assertNothingSent();
    }

    #[DataProvider('vetoedWrites')]
    public function test_write_veto_rolls_back_the_whole_transition(string $operation, string $model, string $eventName): void
    {
        [, $need, $supplier] = $this->fixture($operation === 'convert-to-order' ? 'approved' : 'submitted');
        $needBefore = $need->getRawOriginal();
        $line = $need->items()->firstOrFail();
        $lineBefore = $line->getRawOriginal();
        $event = 'eloquent.'.$eventName.': '.$model;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            $data = match ($operation) {
                'approve' => ['items' => [['id' => $line->id, 'quantity_approved' => '0.2500']]],
                'reject' => ['approval_notes' => 'Not required'],
                default => $this->conversion($supplier),
            };
            $this->post(route('vap-inventory.needs.'.$operation, $need), $data)->assertStatus(409);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame($needBefore, $need->fresh()->getRawOriginal());
        $this->assertSame($lineBefore, $line->fresh()->getRawOriginal());
        $this->assertSame(0, InventoryOrder::where('supplier_id', $supplier->id)->count());
        Notification::assertNothingSent();
    }

    public static function vetoedWrites(): array
    {
        return [
            ['approve', InventoryNeedItem::class, 'updating'],
            ['approve', InventoryNeed::class, 'updating'],
            ['reject', InventoryNeed::class, 'updating'],
            ['convert-to-order', InventoryOrder::class, 'creating'],
            ['convert-to-order', InventoryOrderDetail::class, 'creating'],
            ['convert-to-order', InventoryNeedItem::class, 'updating'],
            ['convert-to-order', InventoryOrder::class, 'updating'],
            ['convert-to-order', InventoryNeed::class, 'updating'],
        ];
    }

    public function test_read_only_member_has_no_decision_or_conversion_controls(): void
    {
        [$user, $need] = $this->fixture();
        $user->syncPermissions([Permission::findOrCreate('view_iorders', 'web')]);
        $this->get(route('vap-inventory.needs.show', $need))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canApprove', false)->where('canConvertToOrder', false));
        $this->post(route('vap-inventory.needs.reject', $need), ['approval_notes' => 'No authority'])->assertForbidden();
        $need->update(['status' => 'approved']);
        $this->get(route('vap-inventory.needs.show', $need))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canApprove', false)->where('canConvertToOrder', false));
    }

    /** @return array{User, InventoryNeed, InventoryItemSupplier} */
    private function fixture(string $status = 'submitted'): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        foreach (['view_iorders', 'edit_iorders', 'add_iorders'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $department = Department::create(['name' => 'Procurement', 'code' => fake()->unique()->bothify('D-######')]);
        $unit = InventoryUnit::create(['code' => fake()->unique()->bothify('U-######'), 'description' => 'Millilitres']);
        $item = InventoryItem::create(['lab_id' => $lab->id, 'name' => 'Material', 'code' => fake()->uuid(), 'unit_id' => $unit->id]);
        $supplier = InventoryItemSupplier::create(['name' => 'Supplier '.fake()->uuid(), 'currency' => 'AOA']);
        $need = InventoryNeed::create([
            'lab_id' => $lab->id, 'department_id' => $department->id, 'reference' => fake()->uuid(),
            'requested_by_id' => $user->id, 'status' => $status, 'submitted_at' => now(),
        ]);
        $need->items()->create([
            'inventory_item_id' => $item->id, 'quantity_requested' => '0.5000',
            'quantity_approved' => $status === 'submitted' ? null : '0.2500',
            'estimated_unit_price' => '1.00', 'status' => $status === 'submitted' ? 'requested' : 'approved',
        ]);
        Notification::fake();

        return [$user, $need->refresh(), $supplier];
    }

    private function conversion(InventoryItemSupplier $supplier): array
    {
        return ['supplier_id' => $supplier->id, 'date' => today()->toDateString()];
    }
}
