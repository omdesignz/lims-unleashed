<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\Occurrence;
use App\Models\Proposal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receipt;
use App\Models\VAPLab;
use App\Models\VAPProposal;
use Illuminate\Database\Query\Builder;

class ProposalActivityAccess
{
    public static function constrain(Builder $query, string $table = 'activity_log'): Builder
    {
        self::constrainStaffAccounts($query, $table);
        self::constrainMemberships($query, $table);
        self::constrainOccurrences($query, $table);
        self::constrainFinancialDocuments($query, $table);
        self::constrainInventoryWarehouses($query, $table);
        self::constrainInventoryItems($query, $table);
        app(LaboratoryWorksheetAccess::class)->constrainActivity($query, $table);
        if (! request()->attributes->has('proposal_laboratory_id')) {
            return $query;
        }

        $proposalTypes = ['proposal', Proposal::class, VAPProposal::class];

        return $query->where(function (Builder $query) use ($table, $proposalTypes): void {
            $query->whereNull("{$table}.subject_type")
                ->orWhereNotIn("{$table}.subject_type", $proposalTypes)
                ->orWhere(function (Builder $query) use ($table, $proposalTypes): void {
                    $query->whereIn("{$table}.subject_type", $proposalTypes)
                        ->whereIn("{$table}.subject_id", VAPProposal::withTrashed()->select('id')->toBase());
                });
        });
    }

    public static function constrainMemberships(Builder $query, string $table = 'activity_log'): Builder
    {
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);
        $canView = $operator?->can('view_users') && VAPLab::query()->whereKey($labId)->exists()
            && request()->hasSession() && ! request()->session()->has('impersonate');
        $logNames = ['laboratory_membership', 'personnel_qualifications'];

        return $query->where(function (Builder $visibility) use ($table, $labId, $canView, $logNames): void {
            $visibility->whereNull("{$table}.log_name")->orWhereNotIn("{$table}.log_name", $logNames);
            if ($canView) {
                $visibility->orWhere(function (Builder $owned) use ($table, $labId, $logNames): void {
                    $owned->whereIn("{$table}.log_name", $logNames)->where("{$table}.properties->lab_id", $labId);
                });
            }
        });
    }

    public static function constrainInventoryWarehouses(Builder $query, string $table = 'activity_log'): Builder
    {
        $types = ['inventory_warehouse', InventoryItemWarehouse::class];
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);
        $canView = $operator?->can('view_iwarehouses') && VAPLab::query()->whereKey($labId)->exists()
            && request()->hasSession() && ! request()->session()->has('impersonate');

        return $query->where(function (Builder $visibility) use ($table, $types, $labId, $canView): void {
            $visibility->where(function (Builder $other) use ($table, $types): void {
                $other->where(fn (Builder $logs): Builder => $logs->whereNull("{$table}.log_name")->orWhere("{$table}.log_name", '!=', 'inventory_warehouse'))
                    ->where(fn (Builder $subjects): Builder => $subjects->whereNull("{$table}.subject_type")->orWhereNotIn("{$table}.subject_type", $types));
            });
            if ($canView) {
                $visibility->orWhere(function (Builder $owned) use ($table, $types, $labId): void {
                    $owned->whereIn("{$table}.subject_type", $types)->whereIn("{$table}.subject_id",
                        InventoryItemWarehouse::withTrashed()->where('lab_id', $labId)->select('id')->toBase())
                        ->where(fn (Builder $context): Builder => $context->whereNull("{$table}.properties->lab_id")->orWhere("{$table}.properties->lab_id", $labId));
                });
            }
        });
    }

    public static function constrainInventoryItems(Builder $query, string $table = 'activity_log'): Builder
    {
        $types = [(new InventoryItem)->getMorphClass(), InventoryItem::class];
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);
        $canView = $operator && VAPLab::query()->whereKey($labId)->exists()
            && request()->hasSession() && ! request()->session()->has('impersonate');

        return $query->where(function (Builder $visibility) use ($table, $types, $labId, $operator, $canView): void {
            $visibility->where(function (Builder $other) use ($table, $types): void {
                $other->where(fn (Builder $logs): Builder => $logs->whereNull("{$table}.log_name")->orWhere("{$table}.log_name", '!=', 'inventory_item'))
                    ->where(fn (Builder $subjects): Builder => $subjects->whereNull("{$table}.subject_type")->orWhereNotIn("{$table}.subject_type", $types));
            });
            if ($canView) {
                $items = app(InventoryCatalogueAccess::class)->constrain(InventoryItem::withTrashed()->where('lab_id', $labId), $operator, 'view');
                $visibility->orWhere(function (Builder $owned) use ($table, $types, $labId, $items): void {
                    $owned->whereIn("{$table}.subject_type", $types)->whereIn("{$table}.subject_id", $items->select('id')->toBase())
                        ->where(fn (Builder $context): Builder => $context->whereNull("{$table}.properties->lab_id")->orWhere("{$table}.properties->lab_id", $labId));
                });
            }
        });
    }

    public static function constrainStaffAccounts(Builder $query, string $table = 'activity_log'): Builder
    {
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);
        $canView = $operator && VAPLab::query()->whereKey($labId)->exists() && request()->hasSession()
            && ! request()->session()->has('impersonate');
        $systemAdministrator = $canView && app(StaffAccountAccess::class)->isSystemAdministrator($operator);

        return $query->where(function (Builder $visibility) use ($table, $operator, $canView, $systemAdministrator): void {
            $visibility->whereNull("{$table}.log_name")->orWhere("{$table}.log_name", '!=', 'staff_account');
            if ($canView) {
                $visibility->orWhere(function (Builder $accounts) use ($table, $operator, $systemAdministrator): void {
                    $accounts->where("{$table}.log_name", 'staff_account');
                    if (! $systemAdministrator) {
                        $accounts->where("{$table}.properties->target_user_id", $operator->id)
                            ->where("{$table}.subject_type", $operator->getMorphClass())->where("{$table}.subject_id", $operator->id);
                    }
                });
            }
        });
    }

    private static function constrainOccurrences(Builder $query, string $table): void
    {
        $types = ['occurrence', Occurrence::class];
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);

        $query->where(function (Builder $visibility) use ($table, $types, $labId, $operator): void {
            $visibility->whereNull("{$table}.subject_type")->orWhereNotIn("{$table}.subject_type", $types);

            if ($operator?->can('view_occurrences')) {
                $visibility->orWhere(function (Builder $occurrences) use ($table, $types, $labId): void {
                    $occurrences->whereIn("{$table}.subject_type", $types)
                        ->whereIn("{$table}.subject_id", Occurrence::withTrashed()->where('lab_id', $labId)->select('id')->toBase());
                });
            }
        });
    }

    public static function constrainFinancialDocuments(Builder $query, string $table = 'activity_log'): Builder
    {
        if (! request()->attributes->has('proposal_laboratory_id')) {
            return $query;
        }
        $labId = (int) request()->attributes->get('proposal_laboratory_id');
        $operator = app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->find(request()->user('web')?->id);
        foreach ([
            'invoice' => [Invoice::class, 'view_invoices'],
            'invoice_item' => [InvoiceItem::class, 'view_invoices'],
            'credit_note' => [CreditNote::class, 'view_credit_notes'],
            'credit_note_item' => [CreditNoteItem::class, 'view_credit_notes'],
            'receipt' => [Receipt::class, 'view_receipts'],
            'receipt_item' => [InvoiceReceipt::class, 'view_receipts'],
            'quote' => [Quote::class, 'view_quotes'],
            'quote_item' => [QuoteItem::class, 'view_quotes'],
            'import_certificate' => [ImportCertificate::class, 'view_import_certificates'],
            'import_certificate_item' => [ImportCertificateItem::class, 'view_import_certificates'],
            'export_certificate' => [ExportCertificate::class, 'view_export_certificates'],
            'export_certificate_item' => [ExportCertificateItem::class, 'view_export_certificates'],
        ] as $alias => [$class, $permission]) {
            $types = [$alias, $class];
            $query->where(function (Builder $visibility) use ($table, $types, $operator, $permission, $class, $labId): void {
                $visibility->whereNull("{$table}.subject_type")->orWhereNotIn("{$table}.subject_type", $types);
                if ($operator?->can($permission)) {
                    $visibility->orWhere(function (Builder $owned) use ($table, $types, $class, $labId): void {
                        $owned->whereIn("{$table}.subject_type", $types)->whereIn("{$table}.subject_id",
                            $class::withTrashed()->where('lab_id', $labId)->select('id')->toBase());
                    });
                }
            });
        }

        return $query;
    }
}
