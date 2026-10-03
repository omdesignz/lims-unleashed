<?php

namespace Tests\Feature;

use App\Jobs\CheckSupplierAssessmentDeadlines;
use App\Models\InventoryItemSupplier;
use App\Models\InventorySupplierAssessment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Notifications\OperationalNotification;
use App\Support\SupplierAssessmentNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupplierAssessmentNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private int $labId;

    private function verifiedAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        $this->labId = $lab->id;
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $this->withSession(['active_lab_id' => $lab->id]);

        return $user;
    }

    public function test_sensitive_supplier_assessment_notifies_other_admins(): void
    {
        Notification::fake();

        $sender = $this->verifiedAdmin();
        $otherAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $otherAdmin->assignRole('admin');
        DB::table('lab_user')->insert(['lab_id' => $this->labId, 'user_id' => $otherAdmin->id]);

        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor Notificação',
            'address' => 'Namibe',
            'currency' => 'AOA',
        ]);

        $this->actingAs($sender)->post(route('supplier-assessments.store'), [
            'inventory_item_supplier_id' => $supplier->id,
            'assessment_date' => now()->toDateString(),
            'next_review_at' => now()->addDays(5)->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'critical',
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 2,
            'responsiveness_score' => 2,
            'approved_supplier' => false,
            'is_active' => true,
        ])->assertRedirect();

        Notification::assertSentTo(
            $otherAdmin,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.supplier_assessment'
        );
    }

    public function test_scheduled_supplier_assessment_checks_emit_due_notifications(): void
    {
        Notification::fake();

        $sender = $this->verifiedAdmin();
        $otherAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $otherAdmin->assignRole('admin');
        DB::table('lab_user')->insert(['lab_id' => $this->labId, 'user_id' => $otherAdmin->id]);

        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor com revisão próxima',
            'address' => 'Malanje',
            'currency' => 'AOA',
        ]);

        $assessment = InventorySupplierAssessment::query()->create([
            'lab_id' => $this->labId,
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $sender->id,
            'assessment_date' => now()->subDays(10)->toDateString(),
            'next_review_at' => now()->addDays(7)->toDateString(),
            'status' => 'approved',
            'risk_level' => 'medium',
            'total_score' => 82,
            'delivery_score' => 4,
            'quality_score' => 4,
            'compliance_score' => 4,
            'responsiveness_score' => 4,
            'approved_supplier' => true,
            'is_active' => true,
        ]);

        $inactive = $assessment->replicate();
        $inactive->is_active = false;
        $inactive->save();

        app(CheckSupplierAssessmentDeadlines::class)->handle(app(SupplierAssessmentNotifier::class));

        Notification::assertSentTo(
            $sender,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.supplier_assessment'
                && $notification->payload['context']['actor_name'] === 'Sistema'
                && $notification->payload['context']['lab_id'] === $this->labId
        );
        Notification::assertSentToTimes($sender, OperationalNotification::class, 1);
        Notification::assertSentToTimes($otherAdmin, OperationalNotification::class, 1);
        Notification::assertSentTo(
            $otherAdmin,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.supplier_assessment'
        );
    }

    public function test_sensitive_assessment_reaches_a_permissioned_role_without_admin_status(): void
    {
        Notification::fake();

        $sender = $this->verifiedAdmin();
        $role = Role::findOrCreate('procurement-reviewer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('view_isuppliers', 'web'));
        $reviewer = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
        $reviewer->assignRole($role);
        DB::table('lab_user')->insert(['lab_id' => $this->labId, 'user_id' => $reviewer->id]);
        $inactiveReviewer = User::factory()->create(['email_verified_at' => now(), 'is_active' => false]);
        $inactiveReviewer->assignRole($role);
        DB::table('lab_user')->insert(['lab_id' => $this->labId, 'user_id' => $inactiveReviewer->id]);
        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor para revisão',
            'currency' => 'AOA',
        ]);
        $assessment = InventorySupplierAssessment::query()->create([
            'lab_id' => $this->labId,
            'inventory_item_supplier_id' => $supplier->id,
            'assessed_by_user_id' => $sender->id,
            'assessment_date' => now()->toDateString(),
            'status' => 'conditional',
            'risk_level' => 'high',
            'total_score' => 40,
            'delivery_score' => 2,
            'quality_score' => 2,
            'compliance_score' => 2,
            'responsiveness_score' => 2,
            'approved_supplier' => false,
            'is_active' => true,
        ]);

        app(SupplierAssessmentNotifier::class)->notifySensitiveAssessment($assessment, $sender);

        Notification::assertSentTo(
            $reviewer,
            OperationalNotification::class,
            fn (OperationalNotification $notification): bool => $notification->payload['key'] === 'inventory.supplier_assessment'
        );
        Notification::assertNotSentTo($sender, OperationalNotification::class);
        Notification::assertNotSentTo($inactiveReviewer, OperationalNotification::class);
    }
}
