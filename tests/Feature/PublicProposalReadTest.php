<?php

namespace Tests\Feature;

use App\Actions\RecordPublicProposalView;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use App\Models\VAPProposalItem;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Services\PublicProposalAccess;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PublicProposalReadTest extends TestCase
{
    use DatabaseTransactions;

    private VAPProposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('filesystems.default', 'local'));
        config(['broadcasting.default' => 'null']);
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Public customer']);
        $site = Warehouse::create(['name' => 'Public site', 'customer_id' => $customer->id]);
        $template = VAPProposalTemplate::create(['name' => 'Public template', 'content' => '<p>Proposal terms</p>', 'user_id' => $user->id]);
        $this->proposal = new VAPProposal([
            'customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'user_id' => $user->id, 'department_id' => Department::factory()->create()->id,
            'template_id' => $template->id, 'status' => 'ACCEPTED',
            'proposal_year' => now()->year, 'unique_hash' => (string) str()->uuid(),
            'details' => ['private' => 'internal-review'], 'file_path' => 'private-proposal.pdf',
        ]);
        $this->proposal->lab_id = VAPLab::factory()->create()->id;
        $this->proposal->save();
        $this->proposal->refresh();
        $this->proposal->complianceAgreement()->create([
            'confidentiality' => true, 'impartiality' => false, 'nondisclosure' => true,
            'acknowledged_at' => now(), 'client_ip' => '192.0.2.25',
        ]);
        VAPProposalItem::create([
            'proposal_id' => $this->proposal->id, 'item_description' => 'Water testing',
            'qty' => 2, 'unit_price' => 50, 'total' => 100,
            'unit_id' => Unit::create(['code' => 'U-'.str()->uuid(), 'description' => 'Test'])->id,
            'extra_data' => ['private' => 'internal-item-review'],
        ]);
    }

    public function test_public_page_exposes_only_commercial_data_and_owner_name(): void
    {
        $this->proposal->user->update([
            'two_factor_secret' => 'private-two-factor-secret',
            'two_factor_recovery_codes' => 'private-recovery-codes',
            'microsoft_data' => ['access_token' => 'private-oauth-token'],
            'id_number' => 'PRIVATE-ID', 'dob' => '1990-01-01',
        ]);

        $this->get(route('vap-proposals.public.show', $this->proposal->unique_hash))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Public/ProposalShow')
            ->where('proposal.proposal_number', $this->proposal->proposal_number)
            ->where('proposal.user', ['name' => $this->proposal->user->name])
            ->where('proposal.customer.name', 'Public customer')
            ->where('proposal.items.0.item_description', 'Water testing')
            ->where('proposal.compliance_agreement.impartiality', false)
            ->missing('proposal.id')->missing('proposal.lab_id')->missing('proposal.user_id')
            ->missing('proposal.details')->missing('proposal.file_path')
            ->missing('proposal.customer.id')->missing('proposal.warehouse.customer_id')
            ->missing('proposal.template.user_id')->missing('proposal.items.0.extra_data')
            ->missing('proposal.items.0.itemable_id')->missing('proposal.items.0.proposal_id')
            ->missing('proposal.compliance_agreement.client_ip'));
    }

    public function test_public_pdf_returns_fresh_bytes_without_mutating_proposal_or_storage(): void
    {
        Storage::put($this->proposal->file_path, 'retained-private-pdf');
        $before = $this->proposal->getRawOriginal();
        $activities = DB::table('activity_log')->count();
        $this->mockRenderer();

        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Report-Studio-Renderer', 'test')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertContent('%PDF-public-test');

        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame($activities, DB::table('activity_log')->count());
        $this->assertSame(['private-proposal.pdf'], Storage::allFiles());
        $this->assertSame('retained-private-pdf', Storage::get($this->proposal->file_path));
    }

    public function test_real_public_pdf_renderer_returns_a_pdf_without_persisting_it(): void
    {
        $before = $this->proposal->getRawOriginal();
        $response = $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertNotEmpty($response->headers->get('X-Report-Studio-Renderer'));
        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame([], Storage::allFiles());
    }

    #[DataProvider('invalidSources')]
    public function test_pdf_and_confirmation_fail_closed_for_invalid_sources(string $mutation): void
    {
        $this->invalidate($mutation);
        $this->mock(ReportStudioPdfRenderer::class, fn (MockInterface $mock) => $mock->shouldNotReceive('renderDocument'));

        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))->assertNotFound();
        $this->get(route('vap-proposals.public.thankyou', $this->proposal->unique_hash))->assertNotFound();
    }

    #[DataProvider('invalidSources')]
    public function test_pdf_does_not_release_bytes_when_source_is_revoked_during_rendering(string $mutation): void
    {
        $this->mockRenderer(fn () => $this->invalidate($mutation));
        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))->assertNotFound();
        $this->assertSame([], Storage::allFiles());
    }

    public static function invalidSources(): array
    {
        return array_map(fn (string $mutation): array => [$mutation], array_combine(
            ['proposal', 'lab', 'customer', 'site', 'lineage', 'unowned', 'token'],
            ['proposal', 'lab', 'customer', 'site', 'lineage', 'unowned', 'token']
        ));
    }

    #[DataProvider('contentMutations')]
    public function test_pdf_requires_retry_when_loaded_content_changes_during_rendering(string $mutation): void
    {
        $this->mockRenderer(function () use ($mutation): void {
            match ($mutation) {
                'proposal' => DB::table('proposals')->where('id', $this->proposal->id)->update(['obs' => 'Changed']),
                'item' => DB::table('proposal_items')->where('proposal_id', $this->proposal->id)->update(['total' => 125]),
                'template' => DB::table('proposal_templates')->where('id', $this->proposal->template_id)->update(['content' => '<p>Changed</p>']),
                'consent' => DB::table('proposal_compliance_agreements')->where('proposal_id', $this->proposal->id)->update(['impartiality' => true]),
                'customer' => DB::table('customers')->where('id', $this->proposal->customer_id)->update(['name' => 'Changed']),
            };
        });
        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))->assertConflict();
        $this->assertSame([], Storage::allFiles());
        $this->assertSame('private-proposal.pdf', $this->proposal->fresh()->file_path);
    }

    public static function contentMutations(): array
    {
        return ['proposal' => ['proposal'], 'item' => ['item'], 'template' => ['template'], 'consent' => ['consent'], 'customer' => ['customer']];
    }

    #[DataProvider('confirmationStatuses')]
    public function test_confirmation_preserves_current_status_without_claiming_an_unrecorded_decision(string $status): void
    {
        DB::table('proposals')->where('id', $this->proposal->id)->update(['status' => $status]);
        $this->get(route('vap-proposals.public.thankyou', $this->proposal->unique_hash))
            ->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('Public/ThankYou')
            ->where('proposal', ['proposal_number' => $this->proposal->proposal_number, 'status' => $status])
            ->where('proposalUrl', route('vap-proposals.public.show', $this->proposal->unique_hash)));
        $this->assertSame($status, $this->proposal->fresh()->status);
    }

    public static function confirmationStatuses(): array
    {
        return ['accepted' => ['ACCEPTED'], 'rejected' => ['REJECTED'], 'pending' => ['PENDING'], 'sent' => ['SENT'], 'viewed' => ['VIEWED'], 'revised' => ['REVISED'], 'expired' => ['EXPIRED']];
    }

    public function test_stale_bound_confirmation_source_cannot_resolve_a_rotated_token(): void
    {
        $snapshot = $this->proposal->fresh();
        $this->invalidate('token');
        try {
            app(PublicProposalAccess::class)->current($snapshot);
            $this->fail('A stale token snapshot must fail closed.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_public_page_rechecks_source_after_view_tracking_before_returning_props(): void
    {
        $snapshot = $this->proposal->fresh();
        $this->mock(RecordPublicProposalView::class, function (MockInterface $mock) use ($snapshot): void {
            $mock->shouldReceive('execute')->once()->andReturnUsing(function () use ($snapshot): VAPProposal {
                $this->invalidate('token');

                return $snapshot;
            });
        });

        $this->get(route('vap-proposals.public.show', $snapshot->unique_hash))->assertNotFound();
    }

    #[DataProvider('sourceReassignments')]
    public function test_pdf_cannot_follow_reassigned_source_identity_during_rendering(string $field): void
    {
        $this->mockRenderer(function () use ($field): void {
            $value = match ($field) {
                'lab_id' => VAPLab::factory()->create()->id,
                'user_id' => User::factory()->create()->id,
                'warehouse_id' => Warehouse::create(['name' => 'Another site', 'customer_id' => $this->proposal->customer_id])->id,
            };
            DB::table('proposals')->where('id', $this->proposal->id)->update([$field => $value]);
        });
        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))->assertNotFound();
        $this->assertSame([], Storage::allFiles());
    }

    public static function sourceReassignments(): array
    {
        return ['laboratory' => ['lab_id'], 'owner' => ['user_id'], 'site' => ['warehouse_id']];
    }

    public function test_renderer_failure_preserves_records_and_retained_files(): void
    {
        Storage::put($this->proposal->file_path, 'retained-private-pdf');
        $before = $this->proposal->getRawOriginal();
        $this->mock(ReportStudioPdfRenderer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('renderDocument')->once()->andThrow(new \RuntimeException('Renderer unavailable'));
        });
        $this->get(route('vap-proposals.public.download', $this->proposal->unique_hash))->assertServerError();
        $this->assertSame($before, $this->proposal->fresh()->getRawOriginal());
        $this->assertSame(['private-proposal.pdf'], Storage::allFiles());
        $this->assertSame('retained-private-pdf', Storage::get($this->proposal->file_path));
    }

    private function mockRenderer(?callable $duringRender = null): void
    {
        $this->mock(ReportStudioPdfRenderer::class, function (MockInterface $mock) use ($duringRender): void {
            $mock->shouldReceive('renderDocument')->once()->andReturnUsing(function () use ($duringRender): array {
                if ($duringRender) {
                    $duringRender();
                }

                return ['content' => '%PDF-public-test', 'renderer' => 'test'];
            });
        });
    }

    private function invalidate(string $mutation): void
    {
        match ($mutation) {
            'proposal' => DB::table('proposals')->where('id', $this->proposal->id)->update(['deleted_at' => now()]),
            'lab' => DB::table('labs')->where('id', $this->proposal->lab_id)->update(['deleted_at' => now()]),
            'customer' => DB::table('customers')->where('id', $this->proposal->customer_id)->update(['deleted_at' => now()]),
            'site' => DB::table('warehouses')->where('id', $this->proposal->warehouse_id)->update(['deleted_at' => now()]),
            'lineage' => DB::table('warehouses')->where('id', $this->proposal->warehouse_id)->update(['customer_id' => Customer::create(['name' => 'Another customer'])->id]),
            'unowned' => DB::table('proposals')->where('id', $this->proposal->id)->update(['lab_id' => null]),
            'token' => DB::table('proposals')->where('id', $this->proposal->id)->update(['unique_hash' => (string) str()->uuid()]),
        };
    }
}
