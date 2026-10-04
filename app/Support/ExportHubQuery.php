<?php

namespace App\Support;

use App\Models\User;
use App\Services\SampleLaboratoryAccess;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

class ExportHubQuery
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forDataset(string $dataset, array $filters): Builder
    {
        return match ($dataset) {
            'activity_log' => $this->activityLog($filters),
            'customers' => $this->customers($filters),
            'warehouses' => $this->warehouses($filters),
            'products' => $this->products($filters),
            'parameters' => $this->parameters($filters),
            'profiles' => $this->profiles($filters),
            'matrixes' => $this->matrixes($filters),
            'invoices' => $this->invoices($filters),
            'quotes' => $this->quotes($filters),
            'credit_notes' => $this->creditNotes($filters),
            'receipts' => $this->receipts($filters),
            'contract_guides' => $this->contractGuides($filters),
            'import_certificates' => $this->importCertificates($filters),
            'export_certificates' => $this->exportCertificates($filters),
            'quality_certificates' => $this->qualityCertificates($filters),
            'customer_requests' => $this->customerRequests($filters),
            'occurrences' => $this->occurrences($filters),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function activityLog(array $filters): Builder
    {
        $query = Activity::query()->toBase()
            ->leftJoin('users as causers', function ($join): void {
                $join->on('causers.id', '=', 'activity_log.causer_id')
                    ->whereIn('activity_log.causer_type', ['user', User::class]);
            })
            ->select([
                'activity_log.id',
                'activity_log.log_name',
                'activity_log.description',
                'activity_log.event',
                'activity_log.causer_id',
                'activity_log.causer_type',
                'causers.name as causer_name',
                'causers.email as causer_email',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.properties',
                'activity_log.batch_uuid',
                'activity_log.created_at',
                'activity_log.updated_at',
            ]);

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('activity_log.description', 'like', $like)
                        ->orWhere('activity_log.log_name', 'like', $like)
                        ->orWhere('activity_log.event', 'like', $like)
                        ->orWhere('activity_log.batch_uuid', 'like', $like)
                        ->orWhere('causers.name', 'like', $like)
                        ->orWhere('causers.email', 'like', $like);
                });
            })
            ->when($filters['description'] ?? null, fn (Builder $query, string $description) => $query->where('activity_log.description', 'like', '%'.$description.'%'))
            ->when($filters['log_name'] ?? null, fn (Builder $query, string $logName) => $query->where('activity_log.log_name', $logName))
            ->when($filters['event'] ?? null, fn (Builder $query, string $event) => $query->where('activity_log.event', $event))
            ->when($filters['subject_type'] ?? null, fn (Builder $query, string $subjectType) => $query->where('activity_log.subject_type', $subjectType))
            ->when($filters['subject_id'] ?? null, fn (Builder $query, int $subjectId) => $query->where('activity_log.subject_id', $subjectId))
            ->when($filters['causer_id'] ?? null, function (Builder $query, int $causerId): void {
                $query->where('activity_log.causer_id', $causerId)
                    ->whereIn('activity_log.causer_type', ['user', User::class]);
            })
            ->when($filters['property'] ?? null, fn (Builder $query, string $property) => $query->where('activity_log.properties', 'like', '%'.$property.'%'))
            ->when($filters['batch_uuid'] ?? null, fn (Builder $query, string $batchUuid) => $query->where('activity_log.batch_uuid', $batchUuid));

        $this->applyDateRange($query, $filters, 'activity_log.created_at');

        return $query->orderByDesc('activity_log.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function customers(array $filters): Builder
    {
        $warehouseCounts = DB::table('warehouses')
            ->selectRaw('customer_id, COUNT(*) as warehouse_count')
            ->whereNull('deleted_at')
            ->groupBy('customer_id');

        $query = DB::table('customers')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'customers.category_id')
            ->leftJoin('warehouses as primary_warehouses', 'primary_warehouses.id', '=', 'customers.warehouse_id')
            ->leftJoinSub($warehouseCounts, 'warehouse_counts', fn ($join) => $join->on('warehouse_counts.customer_id', '=', 'customers.id'))
            ->select([
                'customers.id',
                'customers.code',
                'customers.name',
                'customers.description',
                'customer_categories.name as category',
                'primary_warehouses.code as warehouse_code',
                'primary_warehouses.name as warehouse_name',
                'primary_warehouses.nif',
                'primary_warehouses.address',
                'primary_warehouses.municipality',
                'primary_warehouses.province',
                'primary_warehouses.primary_phone',
                'primary_warehouses.email',
                'primary_warehouses.invoicing_email',
                'primary_warehouses.focal_point',
                'primary_warehouses.focal_point_contact',
                'primary_warehouses.focal_point_email',
                DB::raw('COALESCE(warehouse_counts.warehouse_count, 0) as warehouse_count'),
                'customers.deleted_at',
                'customers.created_at',
                'customers.updated_at',
            ]);

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('customers.name', 'like', $like)
                        ->orWhere('customers.code', 'like', $like)
                        ->orWhere('customers.description', 'like', $like)
                        ->orWhere('customer_categories.name', 'like', $like)
                        ->orWhere('primary_warehouses.name', 'like', $like)
                        ->orWhere('primary_warehouses.nif', 'like', $like);
                });
            })
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('customers.category_id', $categoryId))
            ->when($filters['province'] ?? null, fn (Builder $query, string $province) => $query->where('primary_warehouses.province', $province))
            ->when(($filters['has_primary_site'] ?? 'all') === 'yes', fn (Builder $query) => $query->whereNotNull('customers.warehouse_id'))
            ->when(($filters['has_primary_site'] ?? 'all') === 'no', fn (Builder $query) => $query->whereNull('customers.warehouse_id'));

        $this->applyRecordStatus($query, $filters, 'customers.deleted_at');
        $this->applyDateRange($query, $filters, 'customers.created_at');

        return $query->orderBy('customers.name')->orderBy('customers.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function products(array $filters): Builder
    {
        $query = DB::table('products')
            ->leftJoin('matrixes', 'matrixes.id', '=', 'products.matrix_id')
            ->leftJoin('tax_types', 'tax_types.id', '=', 'products.tax_id')
            ->leftJoin('tax_exemptions', 'tax_exemptions.id', '=', 'products.exemption_id')
            ->select([
                'products.id',
                'products.name',
                'products.description',
                'matrixes.code as matrix_code',
                'matrixes.description as matrix',
                'products.price',
                'products.fixed_price',
                'products.charge_tax',
                'products.tax_percentage',
                'tax_types.name as tax_category',
                'products.withhold_tax',
                'products.exemption_code',
                'tax_exemptions.reason as exemption_reason',
                'tax_exemptions.law as exemption_law',
                'products.deleted_at',
                'products.created_at',
                'products.updated_at',
            ]);

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('products.name', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhere('matrixes.code', 'like', $like)
                        ->orWhere('matrixes.description', 'like', $like);
                });
            })
            ->when($filters['matrix_id'] ?? null, fn (Builder $query, int $matrixId) => $query->where('products.matrix_id', $matrixId))
            ->when(($filters['tax_status'] ?? 'all') === 'taxable', fn (Builder $query) => $query->where('products.charge_tax', true))
            ->when(($filters['tax_status'] ?? 'all') === 'exempt', fn (Builder $query) => $query->where('products.charge_tax', false))
            ->when(($filters['withholding'] ?? 'all') === 'yes', fn (Builder $query) => $query->where('products.withhold_tax', true))
            ->when(($filters['withholding'] ?? 'all') === 'no', fn (Builder $query) => $query->where('products.withhold_tax', false))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('products.price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('products.price', '<=', $filters['max_price']));

        $this->applyRecordStatus($query, $filters, 'products.deleted_at');
        $this->applyDateRange($query, $filters, 'products.created_at');

        return $query->orderBy('products.name')->orderBy('products.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function warehouses(array $filters): Builder
    {
        $query = DB::table('warehouses')
            ->leftJoin('customers', 'customers.id', '=', 'warehouses.customer_id')
            ->select([
                'warehouses.id', 'warehouses.code', 'warehouses.name', 'customers.code as customer_code', 'customers.name as customer',
                'warehouses.nif', 'warehouses.address', 'warehouses.municipality', 'warehouses.province', 'warehouses.primary_phone',
                'warehouses.alternative_phone', 'warehouses.email', 'warehouses.invoicing_email', 'warehouses.focal_point',
                'warehouses.focal_point_contact', 'warehouses.focal_point_email', 'warehouses.deleted_at', 'warehouses.created_at', 'warehouses.updated_at',
            ]);

        $this->applySearch($query, $filters, ['warehouses.code', 'warehouses.name', 'warehouses.nif', 'warehouses.address', 'warehouses.email', 'customers.code', 'customers.name']);
        $query
            ->when($filters['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where('warehouses.customer_id', $customerId))
            ->when($filters['province'] ?? null, fn (Builder $query, string $province) => $query->where('warehouses.province', $province));
        $this->applyRecordStatus($query, $filters, 'warehouses.deleted_at');
        $this->applyDateRange($query, $filters, 'warehouses.created_at');

        return $query->orderBy('customers.name')->orderBy('warehouses.name')->orderBy('warehouses.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function parameters(array $filters): Builder
    {
        $profileCounts = DB::table('parameter_profile')
            ->selectRaw('parameter_id, COUNT(DISTINCT profile_id) as profile_count')
            ->whereNull('deleted_at')
            ->groupBy('parameter_id');

        $query = DB::table('parameters')
            ->leftJoin('tax_types', 'tax_types.id', '=', 'parameters.tax_id')
            ->leftJoin('formulas', 'formulas.id', '=', 'parameters.formula_id')
            ->leftJoinSub($profileCounts, 'profile_counts', fn ($join) => $join->on('profile_counts.parameter_id', '=', 'parameters.id'))
            ->select([
                'parameters.id', 'parameters.code', 'parameters.name', 'parameters.description', 'parameters.price', 'parameters.active',
                'parameters.charge_tax', 'parameters.tax_percentage', 'tax_types.name as tax_category', 'parameters.withhold_tax',
                'parameters.exemption_code', 'parameters.result_type', 'parameters.result_is_qualitative', 'parameters.decimal_places',
                'parameters.optimal_analysis_time', 'parameters.requires_calculation', 'formulas.name as formula',
                DB::raw('COALESCE(profile_counts.profile_count, 0) as profile_count'),
                'parameters.deleted_at', 'parameters.created_at', 'parameters.updated_at',
            ]);

        $this->applySearch($query, $filters, ['parameters.code', 'parameters.name', 'parameters.description', 'formulas.name']);
        $query
            ->when(($filters['enabled'] ?? 'all') === 'yes', fn (Builder $query) => $query->where('parameters.active', true))
            ->when(($filters['enabled'] ?? 'all') === 'no', fn (Builder $query) => $query->where('parameters.active', false))
            ->when(($filters['tax_status'] ?? 'all') === 'taxable', fn (Builder $query) => $query->where('parameters.charge_tax', true))
            ->when(($filters['tax_status'] ?? 'all') === 'exempt', fn (Builder $query) => $query->where('parameters.charge_tax', false))
            ->when(($filters['withholding'] ?? 'all') === 'yes', fn (Builder $query) => $query->where('parameters.withhold_tax', true))
            ->when(($filters['withholding'] ?? 'all') === 'no', fn (Builder $query) => $query->where('parameters.withhold_tax', false))
            ->when($filters['result_type'] ?? null, fn (Builder $query, string $resultType) => $query->where('parameters.result_type', $resultType))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('parameters.price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('parameters.price', '<=', $filters['max_price']));
        $this->applyRecordStatus($query, $filters, 'parameters.deleted_at');
        $this->applyDateRange($query, $filters, 'parameters.created_at');

        return $query->orderBy('parameters.name')->orderBy('parameters.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function profiles(array $filters): Builder
    {
        $parameterStats = DB::table('parameter_profile as pp')
            ->leftJoin('parameters as pp_parameters', 'pp_parameters.id', '=', 'pp.parameter_id')
            ->selectRaw('pp.profile_id, COUNT(DISTINCT pp.parameter_id) as parameter_count, COALESCE(SUM(CASE WHEN pp.count = true OR pp.count IS NULL THEN pp_parameters.price ELSE 0 END), 0) as calculated_price')
            ->whereNull('pp.deleted_at')
            ->groupBy('pp.profile_id');
        $matrixCounts = DB::table('matrix_profile')
            ->selectRaw('profile_id, COUNT(DISTINCT matrix_id) as matrix_count')
            ->whereNull('deleted_at')
            ->groupBy('profile_id');

        $query = DB::table('profiles')
            ->leftJoin('analysis_categories', 'analysis_categories.id', '=', 'profiles.category_id')
            ->leftJoin('departments', 'departments.id', '=', 'analysis_categories.department_id')
            ->leftJoinSub($parameterStats, 'parameter_stats', fn ($join) => $join->on('parameter_stats.profile_id', '=', 'profiles.id'))
            ->leftJoinSub($matrixCounts, 'matrix_counts', fn ($join) => $join->on('matrix_counts.profile_id', '=', 'profiles.id'))
            ->select([
                'profiles.id', 'profiles.code', 'profiles.name', 'profiles.description', 'analysis_categories.code as category_code',
                'analysis_categories.name as category', 'departments.name as department', 'profiles.price as configured_price',
                DB::raw('COALESCE(parameter_stats.calculated_price, 0) as calculated_price'),
                DB::raw('COALESCE(parameter_stats.parameter_count, 0) as parameter_count'),
                DB::raw('COALESCE(matrix_counts.matrix_count, 0) as matrix_count'),
                'profiles.deleted_at', 'profiles.created_at', 'profiles.updated_at',
            ]);

        $this->applySearch($query, $filters, ['profiles.code', 'profiles.name', 'profiles.description', 'analysis_categories.code', 'analysis_categories.name', 'departments.name']);
        $query->when($filters['analysis_category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('profiles.category_id', $categoryId));
        $this->applyRecordStatus($query, $filters, 'profiles.deleted_at');
        $this->applyDateRange($query, $filters, 'profiles.created_at');

        return $query->orderBy('profiles.name')->orderBy('profiles.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function matrixes(array $filters): Builder
    {
        $profileCounts = DB::table('matrix_profile')->selectRaw('matrix_id, COUNT(DISTINCT profile_id) as profile_count')->whereNull('deleted_at')->groupBy('matrix_id');
        $productCounts = DB::table('products')->selectRaw('matrix_id, COUNT(*) as product_count')->whereNull('deleted_at')->groupBy('matrix_id');
        $query = DB::table('matrixes')
            ->leftJoin('tax_types', 'tax_types.id', '=', 'matrixes.tax_id')
            ->leftJoinSub($profileCounts, 'profile_counts', fn ($join) => $join->on('profile_counts.matrix_id', '=', 'matrixes.id'))
            ->leftJoinSub($productCounts, 'product_counts', fn ($join) => $join->on('product_counts.matrix_id', '=', 'matrixes.id'))
            ->select([
                'matrixes.id', 'matrixes.code', 'matrixes.description', 'matrixes.price', 'matrixes.fixed_price', 'matrixes.charge_tax',
                'matrixes.tax_percentage', 'tax_types.name as tax_category', 'matrixes.withhold_tax', 'matrixes.exemption_code',
                DB::raw('COALESCE(profile_counts.profile_count, 0) as profile_count'), DB::raw('COALESCE(product_counts.product_count, 0) as product_count'),
                'matrixes.deleted_at', 'matrixes.created_at', 'matrixes.updated_at',
            ]);

        $this->applySearch($query, $filters, ['matrixes.code', 'matrixes.description']);
        $query
            ->when(($filters['tax_status'] ?? 'all') === 'taxable', fn (Builder $query) => $query->where('matrixes.charge_tax', true))
            ->when(($filters['tax_status'] ?? 'all') === 'exempt', fn (Builder $query) => $query->where('matrixes.charge_tax', false))
            ->when(($filters['withholding'] ?? 'all') === 'yes', fn (Builder $query) => $query->where('matrixes.withhold_tax', true))
            ->when(($filters['withholding'] ?? 'all') === 'no', fn (Builder $query) => $query->where('matrixes.withhold_tax', false))
            ->when(isset($filters['min_price']), fn (Builder $query) => $query->where('matrixes.price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn (Builder $query) => $query->where('matrixes.price', '<=', $filters['max_price']));
        $this->applyRecordStatus($query, $filters, 'matrixes.deleted_at');
        $this->applyDateRange($query, $filters, 'matrixes.created_at');

        return $query->orderBy('matrixes.description')->orderBy('matrixes.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function invoices(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('invoice_items', 'invoice_id');
        $dueDateColumn = Schema::hasColumn('invoices', 'due_date')
            ? 'invoices.due_date'
            : DB::raw('NULL as due_date');
        $query = DB::table('invoices')
            ->where('invoices.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'invoices.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'invoices.user_id')
            ->leftJoin('invoice_categories', 'invoice_categories.id', '=', 'invoices.type_id')
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'invoices.id'))
            ->select([
                'invoices.id', 'invoices.inv_no', 'invoices.date', $dueDateColumn, 'invoices.paid_date', 'customers.code as customer_code',
                'customers.name as customer', 'warehouses.name as warehouse', 'invoice_categories.description as document_type',
                DB::raw('COALESCE(item_counts.item_count, 0) as item_count'), 'invoices.sub_total', 'invoices.tax', 'invoices.discount',
                'invoices.withholding_tax_amount', 'invoices.total', 'invoices.amount_due',
                DB::raw("CASE WHEN invoices.status_code = 'A' THEN 'canceled' WHEN invoices.amount_due <= 0 THEN 'paid' ELSE 'unpaid' END as payment_status"),
                'invoices.payment_method', 'users.name as issued_by', 'invoices.exported_saft', 'invoices.internal_ref', 'invoices.description',
                'invoices.deleted_at', 'invoices.created_at', 'invoices.updated_at',
            ]);

        $this->applySearch($query, $filters, ['invoices.inv_no', 'invoices.internal_ref', 'invoices.description', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyCommercialFilters($query, $filters, 'invoices');
        $query
            ->when(($filters['payment_status'] ?? 'all') === 'paid', fn (Builder $query) => $query->where('invoices.status_code', 'N')->where('invoices.amount_due', '<=', 0))
            ->when(($filters['payment_status'] ?? 'all') === 'unpaid', fn (Builder $query) => $query->where('invoices.status_code', 'N')->where('invoices.amount_due', '>', 0))
            ->when(($filters['payment_status'] ?? 'all') === 'canceled', fn (Builder $query) => $query->where('invoices.status_code', 'A'));
        $this->applyRecordStatus($query, $filters, 'invoices.deleted_at');
        $this->applyDateRange($query, $filters, 'invoices.date');

        return $query->orderByDesc('invoices.date')->orderByDesc('invoices.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function quotes(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('quote_items', 'quote_id');
        $query = DB::table('quotes')
            ->where('quotes.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers', 'customers.id', '=', 'quotes.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'quotes.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'quotes.user_id')
            ->leftJoin('invoices', fn ($join) => $join->on('invoices.id', '=', 'quotes.invoice_id')
                ->where('invoices.lab_id', (int) request()->attributes->get('proposal_laboratory_id', 0)))
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'quotes.id'))
            ->select([
                'quotes.id', 'quotes.quote_no', 'quotes.date', 'quotes.due_date', 'customers.code as customer_code', 'customers.name as customer',
                'warehouses.name as warehouse', DB::raw("'Cotação' as document_type"), DB::raw('COALESCE(item_counts.item_count, 0) as item_count'),
                'quotes.sub_total', 'quotes.tax', 'quotes.discount', 'quotes.withholding_tax_amount', 'quotes.total', 'quotes.converted_to_invoice',
                'invoices.inv_no as invoice_no', 'users.name as issued_by', 'quotes.exported_saft', 'quotes.internal_ref', 'quotes.description',
                'quotes.deleted_at', 'quotes.created_at', 'quotes.updated_at',
            ]);

        $this->applySearch($query, $filters, ['quotes.quote_no', 'quotes.internal_ref', 'quotes.description', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyCommercialFilters($query, $filters, 'quotes');
        $query
            ->when(($filters['converted'] ?? 'all') === 'yes', fn (Builder $query) => $query->where('quotes.converted_to_invoice', true))
            ->when(($filters['converted'] ?? 'all') === 'no', fn (Builder $query) => $query->where('quotes.converted_to_invoice', false));
        $this->applyRecordStatus($query, $filters, 'quotes.deleted_at');
        $this->applyDateRange($query, $filters, 'quotes.date');

        return $query->orderByDesc('quotes.date')->orderByDesc('quotes.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function creditNotes(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('credit_note_items', 'note_id');
        $query = DB::table('credit_notes')
            ->where('credit_notes.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers', 'customers.id', '=', 'credit_notes.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'credit_notes.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'credit_notes.user_id')
            ->leftJoin('invoices', fn ($join) => $join->on('invoices.id', '=', 'credit_notes.invoice_id')
                ->on('invoices.lab_id', '=', 'credit_notes.lab_id'))
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'credit_notes.id'))
            ->select([
                'credit_notes.id', 'credit_notes.note_no', 'credit_notes.date', 'credit_notes.reason', 'invoices.inv_no as invoice_no',
                'customers.code as customer_code', 'customers.name as customer', 'warehouses.name as warehouse',
                DB::raw('COALESCE(item_counts.item_count, 0) as item_count'), 'credit_notes.sub_total', 'credit_notes.total', 'credit_notes.amount',
                'users.name as issued_by', 'credit_notes.exported_saft', 'credit_notes.internal_ref', 'credit_notes.obs',
                'credit_notes.deleted_at', 'credit_notes.created_at', 'credit_notes.updated_at',
            ]);

        $this->applySearch($query, $filters, ['credit_notes.note_no', 'credit_notes.internal_ref', 'invoices.inv_no', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyCommercialFilters($query, $filters, 'credit_notes');
        $query->when($filters['reason'] ?? null, fn (Builder $query, string $reason) => $query->where('credit_notes.reason', $reason));
        $this->applyRecordStatus($query, $filters, 'credit_notes.deleted_at');
        $this->applyDateRange($query, $filters, 'credit_notes.date');

        return $query->orderByDesc('credit_notes.date')->orderByDesc('credit_notes.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function receipts(array $filters): Builder
    {
        $allocations = DB::table('invoice_receipt')
            ->selectRaw('receipt_id, COUNT(DISTINCT invoice_id) as invoice_count, COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->whereNull('deleted_at')
            ->groupBy('receipt_id');
        $query = DB::table('receipts')
            ->where('receipts.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers', 'customers.id', '=', 'receipts.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'receipts.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'receipts.user_id')
            ->leftJoin('payment_categories', 'payment_categories.id', '=', 'receipts.payment_type')
            ->leftJoinSub($allocations, 'allocations', fn ($join) => $join->on('allocations.receipt_id', '=', 'receipts.id'))
            ->select([
                'receipts.id', 'receipts.rec_no', 'receipts.date', 'customers.code as customer_code', 'customers.name as customer',
                'warehouses.name as warehouse', 'payment_categories.name as payment_method', DB::raw('COALESCE(allocations.invoice_count, 0) as invoice_count'),
                DB::raw('COALESCE(allocations.paid_amount, 0) as paid_amount'), 'receipts.description', 'users.name as issued_by',
                'receipts.exported_saft', 'receipts.deleted_at', 'receipts.created_at', 'receipts.updated_at',
            ]);

        $this->applySearch($query, $filters, ['receipts.rec_no', 'receipts.description', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyCommercialFilters($query, $filters, 'receipts', 'allocations.paid_amount');
        $query->when($filters['payment_type_id'] ?? null, fn (Builder $query, int $paymentTypeId) => $query->where('receipts.payment_type', $paymentTypeId));
        $this->applyRecordStatus($query, $filters, 'receipts.deleted_at');
        $this->applyDateRange($query, $filters, 'receipts.date');

        return $query->orderByDesc('receipts.date')->orderByDesc('receipts.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function contractGuides(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('contract_guide_items', 'guide_id');
        $query = DB::table('contract_guides')
            ->leftJoin('customers', 'customers.id', '=', 'contract_guides.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'contract_guides.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'contract_guides.user_id')
            ->leftJoin('lab_codes', 'lab_codes.id', '=', 'contract_guides.collection_id')
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'contract_guides.id'))
            ->select([
                'contract_guides.id', 'contract_guides.guide_no', 'contract_guides.date', 'contract_guides.ref_no', 'customers.code as customer_code',
                'customers.name as customer', 'warehouses.name as warehouse', 'contract_guides.nif', 'contract_guides.entry_point',
                'contract_guides.collection_point', 'contract_guides.du_no', 'contract_guides.bl', 'lab_codes.code as lab_code',
                DB::raw('COALESCE(item_counts.item_count, 0) as item_count'), 'contract_guides.contact', 'contract_guides.email',
                'users.name as issued_by', 'contract_guides.obs', 'contract_guides.deleted_at', 'contract_guides.created_at',
            ]);

        $this->applySearch($query, $filters, ['contract_guides.guide_no', 'contract_guides.ref_no', 'contract_guides.du_no', 'contract_guides.bl', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyPartyFilters($query, $filters, 'contract_guides');
        $this->applyRecordStatus($query, $filters, 'contract_guides.deleted_at');
        $this->applyDateRange($query, $filters, 'contract_guides.date');

        return $query->orderByDesc('contract_guides.date')->orderByDesc('contract_guides.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function importCertificates(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('import_certificate_items', 'certificate_id');
        $query = DB::table('import_certificates')
            ->where('import_certificates.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers as importers', 'importers.id', '=', 'import_certificates.importer_id')
            ->leftJoin('warehouses as importer_sites', 'importer_sites.id', '=', 'import_certificates.importer_warehouse_id')
            ->leftJoin('customers as exporters', 'exporters.id', '=', 'import_certificates.exporter_id')
            ->leftJoin('warehouses as exporter_sites', 'exporter_sites.id', '=', 'import_certificates.exporter_warehouse_id')
            ->leftJoin('trans_categories', 'trans_categories.id', '=', 'import_certificates.trans_type_id')
            ->leftJoin('countries', 'countries.id', '=', 'import_certificates.destination_country_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'import_certificates.currency_id')
            ->leftJoin('users', 'users.id', '=', 'import_certificates.user_id')
            ->leftJoin('invoices', fn ($join) => $join->on('invoices.id', '=', 'import_certificates.invoice_id')
                ->where('invoices.lab_id', (int) request()->attributes->get('proposal_laboratory_id', 0)))
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'import_certificates.id'))
            ->select([
                'import_certificates.id', 'import_certificates.cert_no', 'import_certificates.date', 'importers.name as importer', 'importer_sites.name as importer_site',
                'exporters.name as exporter', 'exporter_sites.name as exporter_site', 'trans_categories.name as transport', 'import_certificates.port_exit',
                'import_certificates.port_entry', 'countries.name as destination_country', 'currencies.code as currency', 'import_certificates.cost_freight',
                'import_certificates.cost_insurance', 'import_certificates.vat', 'import_certificates.vat_cost', 'import_certificates.cost_final',
                DB::raw('COALESCE(item_counts.item_count, 0) as item_count'), 'import_certificates.invoiced', 'invoices.inv_no as invoice_no',
                'import_certificates.authorized_personnel', 'users.name as issued_by', 'import_certificates.deleted_at', 'import_certificates.created_at',
            ]);

        $this->applySearch($query, $filters, ['import_certificates.cert_no', 'import_certificates.port_exit', 'import_certificates.port_entry', 'importers.name', 'exporters.name']);
        $query
            ->when($filters['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where('import_certificates.importer_id', $customerId))
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, int $warehouseId) => $query->where('import_certificates.importer_warehouse_id', $warehouseId));
        $this->applyCertificateFilters($query, $filters, 'import_certificates');

        return $query->orderByDesc('import_certificates.date')->orderByDesc('import_certificates.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCertificates(array $filters): Builder
    {
        $itemCounts = $this->countSubquery('export_certificate_items', 'certificate_id');
        $query = DB::table('export_certificates')
            ->where('export_certificates.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers as exporters', 'exporters.id', '=', 'export_certificates.exporter_id')
            ->leftJoin('warehouses as exporter_sites', 'exporter_sites.id', '=', 'export_certificates.exporter_warehouse_id')
            ->leftJoin('trans_categories', 'trans_categories.id', '=', 'export_certificates.trans_type_id')
            ->leftJoin('countries as origin_countries', 'origin_countries.id', '=', 'export_certificates.country_origin_id')
            ->leftJoin('countries as destination_countries', 'destination_countries.id', '=', 'export_certificates.country_destination_id')
            ->leftJoin('users', 'users.id', '=', 'export_certificates.user_id')
            ->leftJoin('invoices', fn ($join) => $join->on('invoices.id', '=', 'export_certificates.invoice_id')
                ->where('invoices.lab_id', (int) request()->attributes->get('proposal_laboratory_id', 0)))
            ->leftJoinSub($itemCounts, 'item_counts', fn ($join) => $join->on('item_counts.parent_id', '=', 'export_certificates.id'))
            ->select([
                'export_certificates.id', 'export_certificates.cert_no', 'export_certificates.date', 'exporters.name as exporter', 'exporter_sites.name as exporter_site',
                'trans_categories.name as transport', 'origin_countries.name as origin_country', 'export_certificates.origin_city',
                'destination_countries.name as destination_country', 'export_certificates.destination_city', 'export_certificates.expedition_date',
                'export_certificates.expedition_location', DB::raw('COALESCE(item_counts.item_count, 0) as item_count'), 'export_certificates.invoiced',
                'invoices.inv_no as invoice_no', 'export_certificates.authorized_personnel', 'users.name as issued_by', 'export_certificates.obs',
                'export_certificates.deleted_at', 'export_certificates.created_at',
            ]);

        $this->applySearch($query, $filters, ['export_certificates.cert_no', 'export_certificates.origin_city', 'export_certificates.destination_city', 'exporters.name']);
        $query
            ->when($filters['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where('export_certificates.exporter_id', $customerId))
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, int $warehouseId) => $query->where('export_certificates.exporter_warehouse_id', $warehouseId));
        $this->applyCertificateFilters($query, $filters, 'export_certificates');

        return $query->orderByDesc('export_certificates.date')->orderByDesc('export_certificates.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function qualityCertificates(array $filters): Builder
    {
        $revisions = DB::table('quality_certificate_revisions')
            ->selectRaw('quality_certificate_id, COUNT(*) as revision_count, MAX(CASE WHEN is_current = true THEN version END) as current_version')
            ->whereNull('deleted_at')
            ->groupBy('quality_certificate_id');
        $query = DB::table('quality_certificates')
            ->leftJoin('customers', 'customers.id', '=', 'quality_certificates.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'quality_certificates.warehouse_id')
            ->leftJoin('products', 'products.id', '=', 'quality_certificates.product_id')
            ->leftJoin('invoices', fn ($join) => $join->on('invoices.id', '=', 'quality_certificates.invoice_id')
                ->where('invoices.lab_id', (int) request()->attributes->get('proposal_laboratory_id', 0)))
            ->leftJoin('lab_codes', 'lab_codes.id', '=', 'quality_certificates.cl_id')
            ->leftJoin('users as issuers', 'issuers.id', '=', 'quality_certificates.user_id')
            ->leftJoin('users as validators', 'validators.id', '=', 'quality_certificates.validated_by_id')
            ->leftJoinSub($revisions, 'revisions', fn ($join) => $join->on('revisions.quality_certificate_id', '=', 'quality_certificates.id'))
            ->select([
                'quality_certificates.id', 'quality_certificates.code', 'lab_codes.code as lab_code', 'customers.code as customer_code',
                'customers.name as customer', 'warehouses.name as warehouse', 'products.name as product', 'invoices.inv_no as invoice_no',
                'quality_certificates.status', 'quality_certificates.validated_at', DB::raw('COALESCE(validators.name, quality_certificates.validated_by) as validated_by'),
                'quality_certificates.validated_on_behalf_of', DB::raw('COALESCE(revisions.revision_count, 0) as revision_count'),
                'revisions.current_version', 'issuers.name as issued_by', 'quality_certificates.obs', 'quality_certificates.deleted_at',
                'quality_certificates.created_at', 'quality_certificates.updated_at',
            ]);

        $this->applySearch($query, $filters, ['quality_certificates.code', 'lab_codes.code', 'customers.code', 'customers.name', 'products.name', 'invoices.inv_no']);
        $this->applyPartyFilters($query, $filters, 'quality_certificates');
        $query
            ->when($filters['product_id'] ?? null, fn (Builder $query, int $productId) => $query->where('quality_certificates.product_id', $productId))
            ->when(($filters['validation_status'] ?? 'all') === 'validated', fn (Builder $query) => $query->whereNotNull('quality_certificates.validated_at'))
            ->when(($filters['validation_status'] ?? 'all') === 'pending', fn (Builder $query) => $query->whereNull('quality_certificates.validated_at'));
        $this->applyRecordStatus($query, $filters, 'quality_certificates.deleted_at');
        $this->applyDateRange($query, $filters, 'quality_certificates.created_at');

        return $query->orderByDesc('quality_certificates.created_at')->orderByDesc('quality_certificates.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function customerRequests(array $filters): Builder
    {
        $query = DB::table('customer_requests')
            ->where('customer_requests.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('customers', 'customers.id', '=', 'customer_requests.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'customer_requests.warehouse_id')
            ->leftJoin('customer_request_categories', 'customer_request_categories.id', '=', 'customer_requests.category_id')
            ->select([
                'customer_requests.id', 'customer_requests.reference', 'customer_requests.title', 'customer_requests.request_type',
                'customer_requests.status', 'customer_requests.priority', 'customer_request_categories.name as category',
                'customers.code as customer_code', 'customers.name as customer', 'warehouses.name as warehouse', 'customer_requests.contact',
                'customer_requests.email', 'customer_requests.preferred_date', 'customer_requests.submitted_at', 'customer_requests.resolved_at',
                'customer_requests.answered', 'customer_requests.description', 'customer_requests.deleted_at', 'customer_requests.created_at',
            ]);

        $this->applySearch($query, $filters, ['customer_requests.reference', 'customer_requests.title', 'customer_requests.description', 'customers.code', 'customers.name', 'warehouses.name']);
        $this->applyPartyFilters($query, $filters, 'customer_requests');
        $query
            ->when($filters['request_category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('customer_requests.category_id', $categoryId))
            ->when($filters['workflow_status'] ?? null, fn (Builder $query, string $status) => $query->where('customer_requests.status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('customer_requests.priority', $priority));
        $this->applyRecordStatus($query, $filters, 'customer_requests.deleted_at');
        $this->applyDateRange($query, $filters, 'customer_requests.created_at');

        return $query->orderByDesc('customer_requests.created_at')->orderByDesc('customer_requests.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function occurrences(array $filters): Builder
    {
        $query = DB::table('occurrences')
            ->where('occurrences.lab_id', $this->laboratoryAccess->activeLabId())
            ->leftJoin('occurrence_statuses', 'occurrence_statuses.id', '=', 'occurrences.status_id')
            ->leftJoin('occurrence_categories', 'occurrence_categories.id', '=', 'occurrences.category_id')
            ->leftJoin('occurrence_origins', 'occurrence_origins.id', '=', 'occurrences.origin_id')
            ->leftJoin('departments', 'departments.id', '=', 'occurrences.department_id')
            ->leftJoin('users', 'users.id', '=', 'occurrences.user_id')
            ->select([
                'occurrences.id', 'occurrences.occurrence_no', 'occurrences.date_reported', 'occurrence_statuses.name as status',
                'occurrence_categories.name as category', 'occurrence_origins.name as origin', 'departments.name as department',
                'occurrences.responsible_name', 'users.name as responsible_user', 'occurrences.issue_description', 'occurrences.analysis',
                'occurrences.corrective_action', 'occurrences.implementation_date', 'occurrences.date_resolved', 'occurrences.date_closed',
                'occurrences.was_effective', 'occurrences.client_acceptance', 'occurrences.update_risk_matrix', 'occurrences.deleted_at', 'occurrences.created_at',
            ]);

        $this->applySearch($query, $filters, ['occurrences.occurrence_no', 'occurrences.issue_description', 'occurrences.analysis', 'occurrences.corrective_action', 'occurrences.responsible_name']);
        $query
            ->when($filters['occurrence_status_id'] ?? null, fn (Builder $query, int $statusId) => $query->where('occurrences.status_id', $statusId))
            ->when($filters['occurrence_category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('occurrences.category_id', $categoryId))
            ->when($filters['occurrence_origin_id'] ?? null, fn (Builder $query, int $originId) => $query->where('occurrences.origin_id', $originId))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $departmentId) => $query->where('occurrences.department_id', $departmentId));
        $this->applyRecordStatus($query, $filters, 'occurrences.deleted_at');
        $this->applyDateRange($query, $filters, 'occurrences.date_reported');

        return $query->orderByDesc('occurrences.date_reported')->orderByDesc('occurrences.id');
    }

    /**
     * @return array<string, array<int, array{value: mixed, label: string}>>
     */
    public function filterOptions(string $dataset): array
    {
        $options = match ($dataset) {
            'activity_log' => [
                'log_names' => $this->simpleOptions('activity_log', 'log_name'),
                'events' => $this->simpleOptions('activity_log', 'event'),
                'subject_types' => Activity::query()->toBase()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type')->map(fn (string $value): array => ['value' => $value, 'label' => class_basename($value)])->values()->all(),
                'actors' => Activity::query()->toBase()->join('users', 'users.id', '=', 'activity_log.causer_id')->whereIn('activity_log.causer_type', ['user', User::class])->select(['users.id', 'users.name', 'users.email'])->distinct()->orderBy('users.name')->get()->map(fn (object $user): array => ['value' => $user->id, 'label' => trim($user->name.' · '.$user->email, ' ·')])->all(),
            ],
            'customers' => [
                'categories' => DB::table('customer_categories')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name'])->map(fn (object $row): array => ['value' => $row->id, 'label' => $row->name])->all(),
                'provinces' => $this->simpleOptions('warehouses', 'province', true),
            ],
            'products' => [
                'matrixes' => DB::table('matrixes')->whereNull('deleted_at')->orderBy('description')->get(['id', 'code', 'description'])->map(fn (object $row): array => ['value' => $row->id, 'label' => trim($row->code.' · '.$row->description, ' ·')])->all(),
            ],
            'warehouses' => [
                'customers' => $this->recordOptions('customers'),
                'provinces' => $this->simpleOptions('warehouses', 'province', true),
            ],
            'parameters' => [
                'result_types' => $this->simpleOptions('parameters', 'result_type'),
            ],
            'profiles' => [
                'analysis_categories' => $this->recordOptions('analysis_categories', 'name', 'code'),
            ],
            'invoices', 'quotes', 'credit_notes', 'contract_guides' => $this->partyOptions(),
            'receipts' => $this->partyOptions() + [
                'payment_types' => $this->recordOptions('payment_categories'),
            ],
            'import_certificates', 'export_certificates' => $this->partyOptions() + [
                'transport_types' => $this->recordOptions('trans_categories'),
            ],
            'quality_certificates' => $this->partyOptions() + [
                'products' => $this->recordOptions('products'),
            ],
            'customer_requests' => $this->partyOptions() + [
                'request_categories' => $this->recordOptions('customer_request_categories'),
                'workflow_statuses' => $this->customerRequests([])->reorder()->distinct()->pluck('customer_requests.status')->map(fn ($value): array => ['value' => $value, 'label' => $value])->all(),
                'priorities' => $this->customerRequests([])->reorder()->distinct()->pluck('customer_requests.priority')->map(fn ($value): array => ['value' => $value, 'label' => $value])->all(),
            ],
            'occurrences' => [
                'occurrence_statuses' => $this->recordOptions('occurrence_statuses'),
                'occurrence_categories' => $this->recordOptions('occurrence_categories'),
                'occurrence_origins' => $this->recordOptions('occurrence_origins'),
                'departments' => $this->recordOptions('departments'),
            ],
            default => [],
        };

        return $options;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function simpleOptions(string $table, string $column, bool $excludeDeleted = false): array
    {
        $query = $table === 'activity_log' ? Activity::query()->toBase() : DB::table($table);

        return $query
            ->when($excludeDeleted, fn (Builder $query) => $query->whereNull('deleted_at'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn (string $value): array => ['value' => $value, 'label' => $value])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function recordOptions(string $table, string $labelColumn = 'name', ?string $codeColumn = null): array
    {
        $columns = array_values(array_filter(['id', $labelColumn, $codeColumn]));

        return DB::table($table)
            ->when(Schema::hasColumn($table, 'deleted_at'), fn (Builder $query) => $query->whereNull('deleted_at'))
            ->orderBy($labelColumn)
            ->get($columns)
            ->map(function (object $row) use ($labelColumn, $codeColumn): array {
                $label = $row->{$labelColumn};

                if ($codeColumn) {
                    $label = trim(($row->{$codeColumn} ?? '').' · '.$label, ' ·');
                }

                return ['value' => (int) $row->id, 'label' => $label];
            })
            ->all();
    }

    /**
     * @return array<string, array<int, array{value: int, label: string}>>
     */
    private function partyOptions(): array
    {
        return [
            'customers' => $this->recordOptions('customers'),
            'warehouses' => $this->recordOptions('warehouses'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    private function applySearch(Builder $query, array $filters, array $columns): void
    {
        $query->when($filters['search'] ?? null, function (Builder $query, string $search) use ($columns): void {
            $like = '%'.$search.'%';

            $query->where(function (Builder $query) use ($columns, $like): void {
                foreach ($columns as $index => $column) {
                    $index === 0 ? $query->where($column, 'like', $like) : $query->orWhere($column, 'like', $like);
                }
            });
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyPartyFilters(Builder $query, array $filters, string $table): void
    {
        $query
            ->when($filters['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where("{$table}.customer_id", $customerId))
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, int $warehouseId) => $query->where("{$table}.warehouse_id", $warehouseId));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCommercialFilters(Builder $query, array $filters, string $table, ?string $totalColumn = null): void
    {
        $this->applyPartyFilters($query, $filters, $table);
        $column = $totalColumn ?? "{$table}.total";

        $query
            ->when(isset($filters['min_total']), fn (Builder $query) => $query->where($column, '>=', $filters['min_total']))
            ->when(isset($filters['max_total']), fn (Builder $query) => $query->where($column, '<=', $filters['max_total']));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCertificateFilters(Builder $query, array $filters, string $table): void
    {
        $query
            ->when($filters['transport_type_id'] ?? null, fn (Builder $query, int $transportTypeId) => $query->where("{$table}.trans_type_id", $transportTypeId))
            ->when(($filters['invoiced'] ?? 'all') === 'yes', fn (Builder $query) => $query->where("{$table}.invoiced", true))
            ->when(($filters['invoiced'] ?? 'all') === 'no', fn (Builder $query) => $query->where("{$table}.invoiced", false));
        $this->applyRecordStatus($query, $filters, "{$table}.deleted_at");
        $this->applyDateRange($query, $filters, "{$table}.date");
    }

    private function countSubquery(string $table, string $foreignKey): Builder
    {
        return DB::table($table)
            ->selectRaw("{$foreignKey} as parent_id, COUNT(*) as item_count")
            ->whereNull('deleted_at')
            ->groupBy($foreignKey);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateRange(Builder $query, array $filters, string $column): void
    {
        $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where($column, '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where($column, '<=', Carbon::parse($date)->endOfDay()));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyRecordStatus(Builder $query, array $filters, string $column): void
    {
        match ($filters['status'] ?? 'active') {
            'archived' => $query->whereNotNull($column),
            'all' => null,
            default => $query->whereNull($column),
        };
    }
}
