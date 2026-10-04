<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\EnvironmentalCondition;
use App\Models\InventorySupplierAssessment;
use App\Models\ManagementReview;
use App\Models\PersonnelQualification;
use App\Models\ResponsibilityMatrixEntry;
use App\Models\UncertaintySource;
use App\Models\VAPFile;
use App\Models\VAPNonConformity;
use App\Services\SampleLaboratoryAccess;
use Inertia\Inertia;
use Inertia\Response;

class QMSController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $access) {}

    public function index(): Response
    {
        $labId = $this->access->activeLabId();
        $qualifications = PersonnelQualification::query()
            ->where('lab_id', $labId)
            ->with(['user:id,name', 'department:id,name', 'qualifiedBy:id,name'])
            ->where('is_active', true)
            ->orderBy('authorized_until')
            ->get();

        $qualificationMonitoring = $qualifications
            ->map(function (PersonnelQualification $qualification) {
                return array_merge(
                    $qualification->toMonitoringArray(),
                    [
                        'user' => [
                            'id' => $qualification->user?->id,
                            'name' => $qualification->user?->name,
                        ],
                    ]
                );
            });

        $expiringQualifications = $qualificationMonitoring
            ->filter(function (array $qualification) {
                return in_array($qualification['monitoring_status'], ['expired', 'expiring_critical', 'expiring_soon'], true);
            })
            ->sortBy([
                ['days_until_expiry', 'asc'],
                ['authorized_until', 'asc'],
            ])
            ->take(12)
            ->values();

        $followUpQueue = $qualificationMonitoring
            ->filter(function (array $qualification) {
                return in_array($qualification['follow_up_state'], ['overdue', 'due_soon', 'scheduled'], true);
            })
            ->sortBy([
                ['follow_up_due_at', 'asc'],
                ['authorized_until', 'asc'],
            ])
            ->take(12)
            ->values();

        $renewalReadyQualifications = $qualificationMonitoring
            ->filter(function (array $qualification) {
                return in_array($qualification['renewal_readiness'], ['ready_for_review', 'training_pending', 'missing_evidence'], true);
            })
            ->take(12)
            ->values();

        $dueDocumentReviewQuery = VAPFile::query()
            ->where('lab_id', $labId)
            ->with(['owner:id,name'])
            ->whereNotNull('review_due_at')
            ->whereDate('review_due_at', '<=', now()->addDays(45))
            ->orderBy('review_due_at');
        $dueDocumentReviews = (clone $dueDocumentReviewQuery)
            ->limit(12)
            ->get(['id', 'name', 'owner_id', 'review_due_at', 'status']);

        $dueSupplierAssessmentQuery = InventorySupplierAssessment::query()
            ->where('lab_id', $labId)
            ->with(['supplier:id,name', 'department:id,name'])
            ->whereNotNull('next_review_at')
            ->whereDate('next_review_at', '<=', now()->addDays(45))
            ->orderBy('next_review_at');
        $dueSupplierAssessments = (clone $dueSupplierAssessmentQuery)
            ->limit(12)
            ->get();

        $receivingNonConformityQuery = VAPNonConformity::query()
            ->where('lab_id', $labId)
            ->with(['department:id,name', 'reportedByUser:id,name'])
            ->where('occurrence_area', 'procurement_receipt')
            ->whereNotIn('status', ['closed', 'resolved'])
            ->latest('reported_at');
        $receivingNonConformities = (clone $receivingNonConformityQuery)
            ->limit(12)
            ->get(['id', 'department_id', 'reported_by_id', 'nc_number', 'title', 'status', 'severity', 'reported_at', 'batch_number']);

        return Inertia::render('QMS/Index', [
            'summary' => [
                'open_complaints' => Complaint::query()->where('lab_id', $labId)->whereNotIn('status', ['resolved', 'closed'])->count(),
                'open_non_conformities' => VAPNonConformity::query()->where('lab_id', $labId)->whereNotIn('status', ['closed', 'resolved'])->count(),
                'scheduled_management_reviews' => ManagementReview::query()->where('lab_id', $labId)->whereDate('review_date', '>=', now()->toDateString())->count(),
                'expiring_qualifications' => $qualificationMonitoring->whereIn('monitoring_status', ['expired', 'expiring_critical', 'expiring_soon'])->count(),
                'expired_qualifications' => $qualificationMonitoring->where('monitoring_status', 'expired')->count(),
                'renewal_ready_qualifications' => $qualificationMonitoring->where('renewal_readiness', 'ready_for_review')->count(),
                'qualifications_missing_evidence' => $qualificationMonitoring->where('renewal_readiness', 'missing_evidence')->count(),
                'qualification_followups_due' => $qualificationMonitoring->whereIn('follow_up_state', ['overdue', 'due_soon'])->count(),
                'responsibility_assignments' => ResponsibilityMatrixEntry::query()->where('lab_id', $labId)->where('is_active', true)->count(),
                'uncertainty_sources' => UncertaintySource::query()->where('lab_id', $labId)->where('is_active', true)->count(),
                'supplier_assessments_due' => $dueSupplierAssessmentQuery->count(),
                'suppliers_high_risk' => InventorySupplierAssessment::query()->where('lab_id', $labId)->whereIn('risk_level', ['high', 'critical'])->count(),
                'receiving_non_conformities_open' => $receivingNonConformityQuery->count(),
                'documents_due_review' => $dueDocumentReviewQuery->count(),
                'environmental_entries_today' => EnvironmentalCondition::query()->where('lab_id', $labId)->whereDate('recorded_at', today())->count(),
            ],
            'expiringQualifications' => $expiringQualifications,
            'qualificationFollowUps' => $followUpQueue,
            'renewalReadyQualifications' => $renewalReadyQualifications,
            'dueSupplierAssessments' => $dueSupplierAssessments,
            'receivingNonConformities' => $receivingNonConformities,
            'dueDocumentReviews' => $dueDocumentReviews,
        ]);
    }
}
