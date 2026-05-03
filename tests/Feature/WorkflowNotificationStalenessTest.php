<?php

namespace Tests\Feature;

use App\Jobs\CheckLaboratoryWorkflowStaleness;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WorkflowNotificationStalenessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_staleness_job_creates_notifications_for_pending_samples(): void
    {
        $admin = $this->verifiedAdmin();
        $customer = Customer::query()->firstOrFail();
        $department = Department::query()->firstOrFail();
        $lab = VAPLab::query()->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();

        $sample = VAPSampleEntry::query()->create([
            'name' => 'Stale Notification Sample',
            'sample_type' => 'ROTINA',
            'customer_id' => $customer->id,
            'lab_id' => $lab->id,
            'department_id' => $department->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'POR_INICIAR',
            'received_by_id' => $admin->id,
            'received_by_label' => $admin->name,
            'sample_year' => now()->year,
            'received_at' => now()->subDays(5),
        ]);

        $sample->generateCode();
        $sample->save();
        $sample->forceFill([
            'updated_at' => now()->subDays(5),
        ])->saveQuietly();

        $initialNotificationCount = $warehouse->notifications()->count() + $admin->notifications()->count();

        app(CheckLaboratoryWorkflowStaleness::class)->handle(app(\App\Support\LaboratoryWorkflowNotifier::class));

        $this->assertGreaterThan(
            $initialNotificationCount,
            $warehouse->fresh()->notifications()->count() + $admin->fresh()->notifications()->count()
        );
    }
}
