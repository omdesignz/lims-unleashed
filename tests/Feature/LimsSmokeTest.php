<?php

namespace Tests\Feature;

use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Occurrence;
use App\Models\Proposal;
use App\Models\QualityCertificate;
use App\Models\Role;
use App\Models\User;
use App\Models\Customer;
use App\Models\VAPNonConformity;
use Tests\TestCase;

class LimsSmokeTest extends TestCase
{
    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for smoke testing.');

        return $admin;
    }

    public function test_verified_admin_can_open_core_lims_pages(): void
    {
        $user = $this->verifiedAdmin();

        $routes = [
            'dashboard',
            'analysis.index',
            'samples.index',
            'standards.index',
            'occurrences.index',
            'complaints.index',
            'customers.index',
            'directcollections.index',
            'programmedcollections.index',
            'management-reviews.index',
            'qualitycertificates.index',
            'importcertificates.index',
            'exportcertificates.index',
            'inventory.index',
            'iequipments.index',
            'systemactivity.index',
            'vap_samples.index',
            'vap_non_conformities.index',
            'worksheets.index',
        ];

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get(route($route));

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected route [%s] to load successfully, got HTTP %d.',
                    $route,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_iso_17025_audit_and_record_pages(): void
    {
        $user = $this->verifiedAdmin();
        $certificate = QualityCertificate::query()->first();
        $occurrence = Occurrence::query()->first();
        $vapNonConformity = VAPNonConformity::query()->first();
        $importCertificate = ImportCertificate::query()->first();
        $exportCertificate = ExportCertificate::query()->first();

        $this->assertNotNull($certificate, 'Expected at least one quality certificate for ISO smoke testing.');
        $this->assertNotNull($occurrence, 'Expected at least one occurrence for smoke testing.');

        $checks = [
            route('qualitycertificates.show', $certificate),
            route('qualitycertificates.iso-revisions.index', $certificate),
            route('qualitycertificates.iso-revisions.audit-trail', $certificate),
            route('qualitycertificates.getApprove', $certificate),
            route('occurrences.show', $occurrence),
            route('vap_samples.reports'),
            route('vap_samples.samples.stats'),
            route('vap_samples.discards.stats'),
            route('dashboard.export'),
        ];

        if ($vapNonConformity) {
            $checks[] = route('vap_non_conformities.show', $vapNonConformity);
        }

        if ($importCertificate) {
            $checks[] = route('importcertificates.show', $importCertificate);
        }

        if ($exportCertificate) {
            $checks[] = route('exportcertificates.show', $exportCertificate);
        }

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_verified_admin_can_open_recent_customer_settings_backup_and_inventory_analytics_pages(): void
    {
        $user = $this->verifiedAdmin();
        $customer = Customer::query()->first();

        $this->assertNotNull($customer, 'Expected at least one customer for admin smoke testing.');

        $checks = [
            route('customers.show', $customer),
            route('generalsettings.index'),
            route('systembackups.backups'),
            route('systembackups.statuses'),
            route('systembackups.index'),
            route('environmental-conditions.index'),
            route('vap-inventory.items.index'),
            route('vap-inventory.analytics.index'),
            route('vap-inventory.analytics.data'),
        ];

        $failures = [];

        foreach ($checks as $url) {
            $response = $this->actingAs($user)->get($url);

            if (! $response->isSuccessful()) {
                $failures[] = sprintf(
                    'Expected [%s] to load successfully, got HTTP %d.',
                    $url,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_proposal_qr_accessor_returns_svg_data_uri(): void
    {
        $proposal = Proposal::query()->first();

        $this->assertNotNull($proposal, 'Expected at least one proposal for QR smoke testing.');
        $this->assertStringStartsWith('data:image/svg+xml', $proposal->qr);
    }
}
