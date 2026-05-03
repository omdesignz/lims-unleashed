<?php

namespace Tests\Feature;

use App\Jobs\CheckSupplierAssessmentDeadlines;
use App\Models\InventoryItemSupplier;
use App\Models\InventorySupplierAssessment;
use App\Models\Role;
use App\Models\User;
use App\Notifications\GlobalNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupplierAssessmentNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
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

        Notification::assertSentTo($otherAdmin, GlobalNotification::class);
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

        $supplier = InventoryItemSupplier::query()->create([
            'name' => 'Fornecedor com revisão próxima',
            'address' => 'Malanje',
            'currency' => 'AOA',
        ]);

        InventorySupplierAssessment::query()->create([
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

        app(CheckSupplierAssessmentDeadlines::class)->handle(app(\App\Support\SupplierAssessmentNotifier::class));

        Notification::assertSentTo($otherAdmin, GlobalNotification::class);
    }
}
