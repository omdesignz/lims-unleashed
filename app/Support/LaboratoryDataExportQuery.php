<?php

namespace App\Support;

use App\Models\LabCode;
use App\Services\SampleLaboratoryAccess;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LaboratoryDataExportQuery
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function pendingWorksheet(array $filters): Builder
    {
        $insertedResults = DB::table('results')
            ->select(['sample_id', 'parameter_id'])
            ->whereNull('deleted_at')
            ->whereNotNull('inserted_date')
            ->groupBy(['sample_id', 'parameter_id']);

        $query = DB::table('analysis')
            ->join('samples', 'samples.id', '=', 'analysis.sample_id')
            ->join('profiles', 'profiles.id', '=', 'analysis.profile_id')
            ->leftJoin('departments', 'departments.id', '=', 'analysis.department_id')
            ->join('lab_codes', 'lab_codes.id', '=', 'samples.cl_id')
            ->join('collection_product as collection_products', 'collection_products.id', '=', 'lab_codes.collection_id')
            ->leftJoin('customers', 'customers.id', '=', 'collection_products.customer_id')
            ->leftJoin('products', 'products.id', '=', 'collection_products.product_id')
            ->join('sample_entries', 'sample_entries.collection_product_id', '=', 'collection_products.id')
            ->join(DB::raw("LATERAL jsonb_to_recordset(COALESCE(sample_entries.client_submitted_info::jsonb -> 'required_parameters', '[]'::jsonb)) AS issued_parameter(id bigint, code text, name text, profile_ids jsonb, profile_definitions jsonb, optimal_analysis_time text)"), function ($join): void {
                $join->whereRaw('issued_parameter.profile_ids @> jsonb_build_array(analysis.profile_id)');
            })
            ->leftJoin(DB::raw("LATERAL jsonb_to_recordset(COALESCE(issued_parameter.profile_definitions, '[]'::jsonb)) AS issued_definition(profile_id bigint, unit_code text, unit_label text, protocol_label text, standard_label text, nwp_label text, dilutions text)"), function ($join): void {
                $join->on('issued_definition.profile_id', '=', 'analysis.profile_id');
            })
            ->leftJoinSub($insertedResults, 'inserted_results', function ($join): void {
                $join->on('inserted_results.sample_id', '=', 'analysis.sample_id')
                    ->on('inserted_results.parameter_id', '=', 'issued_parameter.id');
            })
            ->whereNull('analysis.deleted_at')
            ->whereColumn('analysis.cl_id', 'lab_codes.id')
            ->whereNull('analysis.end_date')
            ->whereNull('samples.deleted_at')
            ->whereNull('lab_codes.deleted_at')
            ->whereNull('collection_products.deleted_at')
            ->whereNull('sample_entries.deleted_at')
            ->where('sample_entries.lab_id', $this->laboratoryAccess->activeLabId())
            ->whereIn('lab_codes.id', LabCode::query()->forLaboratory($this->laboratoryAccess->activeLabId())->select('lab_codes.id'))
            ->whereNull('inserted_results.sample_id')
            ->select([
                'analysis.id as analysis_id',
                'analysis.department_id',
                'analysis.entry_date',
                'analysis.col_date',
                'analysis.created_at as queued_at',
                'samples.id as sample_id',
                'samples.code as sample_code',
                'lab_codes.code as laboratory_code',
                'sample_entries.id as sample_entry_id',
                'sample_entries.code as sample_entry_code',
                'sample_entries.status as sample_entry_status',
                'departments.name as department',
                'customers.name as customer',
                'products.name as product',
                'profiles.name as profile',
                'issued_parameter.id as parameter_id',
                'issued_parameter.code as parameter_code',
                'issued_parameter.name as parameter',
                'issued_parameter.optimal_analysis_time',
                DB::raw('COALESCE(issued_definition.unit_code, issued_definition.unit_label) as unit'),
                'issued_definition.protocol_label as protocol',
                'issued_definition.standard_label as standard',
                'issued_definition.nwp_label as nwp',
                'issued_definition.dilutions',
            ]);

        $this->applyPendingFilters($query, $filters);

        return $query
            ->orderByRaw('COALESCE(analysis.entry_date, analysis.col_date, DATE(analysis.created_at))')
            ->orderBy('analysis.id')
            ->orderBy('issued_parameter.name');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function auditedResults(array $filters): Builder
    {
        $analysisContext = DB::table('analysis')
            ->whereNull('deleted_at')
            ->selectRaw('sample_id, profile_id, MIN(id) as analysis_id, MIN(department_id) as department_id')
            ->groupBy(['sample_id', 'profile_id']);

        $query = DB::table('results')
            ->join('samples', 'samples.id', '=', 'results.sample_id')
            ->leftJoinSub($analysisContext, 'analysis_context', function ($join): void {
                $join->on('analysis_context.sample_id', '=', 'results.sample_id')
                    ->on('analysis_context.profile_id', '=', 'results.profile_id');
            })
            ->leftJoin('departments', 'departments.id', '=', 'analysis_context.department_id')
            ->join('lab_codes', 'lab_codes.id', '=', 'samples.cl_id')
            ->join('collection_product as collection_products', 'collection_products.id', '=', 'lab_codes.collection_id')
            ->leftJoin('customers', 'customers.id', '=', 'collection_products.customer_id')
            ->join('sample_entries', 'sample_entries.collection_product_id', '=', 'collection_products.id')
            ->leftJoin('users as inserted_users', 'inserted_users.id', '=', 'results.inserted_by_id')
            ->leftJoin('users as verified_users', 'verified_users.id', '=', 'results.verified_by_id')
            ->leftJoin('users as approved_users', 'approved_users.id', '=', 'results.approved_by_id')
            ->whereNull('results.deleted_at')
            ->whereNull('samples.deleted_at')
            ->whereNull('lab_codes.deleted_at')
            ->whereNull('collection_products.deleted_at')
            ->whereNull('sample_entries.deleted_at')
            ->where('sample_entries.lab_id', $this->laboratoryAccess->activeLabId())
            ->whereIn('lab_codes.id', LabCode::query()->forLaboratory($this->laboratoryAccess->activeLabId())->select('lab_codes.id'))
            ->whereNotNull('results.inserted_date')
            ->select([
                'results.id as result_id',
                'results.sample_id',
                'analysis_context.analysis_id',
                'analysis_context.department_id',
                'samples.code as sample_code',
                DB::raw('COALESCE(results.code_label, lab_codes.code) as laboratory_code'),
                'sample_entries.id as sample_entry_id',
                'sample_entries.code as sample_entry_code',
                'departments.name as department',
                'customers.name as customer',
                'results.product_label as product',
                'results.parameter_label as parameter',
                'results.unit_label as unit',
                'results.protocol_label as protocol',
                'results.standard_label as standard',
                'results.nwp_label as nwp',
                'results.inserted_value',
                'results.verified_value',
                'results.approved_value',
                'results.uncertainty_value',
                'results.inserted_date',
                'results.verified_date',
                'results.approved_date',
                DB::raw('COALESCE(inserted_users.name, results.inserted_by) as inserted_by'),
                DB::raw('COALESCE(verified_users.name, results.verified_by) as verified_by'),
                DB::raw('COALESCE(approved_users.name, results.approved_by) as approved_by'),
                'results.updated_at',
            ]);

        $this->applyAuditFilters($query, $filters);

        return $query
            ->orderByRaw('COALESCE(results.approved_date, results.verified_date, results.inserted_date) DESC')
            ->orderByDesc('results.id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function pendingSummary(array $filters): array
    {
        $query = $this->pendingWorksheet($filters);

        return [
            'tasks' => (clone $query)->count(),
            'samples' => (clone $query)->distinct()->count('analysis.sample_id'),
            'analyses' => (clone $query)->distinct()->count('analysis.id'),
            'departments' => (clone $query)->whereNotNull('analysis.department_id')->distinct()->count('analysis.department_id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function auditSummary(array $filters): array
    {
        $summaryFilters = [...$filters, 'stage' => 'all'];
        $query = $this->auditedResults($summaryFilters);
        $summary = (clone $query)
            ->reorder()
            ->select([])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN results.verified_date IS NULL THEN 1 ELSE 0 END) as inserted')
            ->selectRaw('SUM(CASE WHEN results.verified_date IS NOT NULL AND results.approved_date IS NULL THEN 1 ELSE 0 END) as verified')
            ->selectRaw('SUM(CASE WHEN results.approved_date IS NOT NULL THEN 1 ELSE 0 END) as approved')
            ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'inserted' => (int) ($summary->inserted ?? 0),
            'verified' => (int) ($summary->verified ?? 0),
            'approved' => (int) ($summary->approved ?? 0),
            'samples' => (clone $query)->distinct()->count('results.sample_id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPendingRow(object $row): array
    {
        return [
            ...((array) $row),
            'work_date' => $this->displayDate($row->entry_date ?? $row->col_date ?? $row->queued_at),
            'analysis_url' => route('analysis.edit', $row->analysis_id),
            'sample_entry_url' => $row->sample_entry_id ? route('vap_samples.show', $row->sample_entry_id) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentAuditRow(object $row): array
    {
        $stage = $this->auditStage($row);

        return [
            ...((array) $row),
            'stage' => $stage,
            'stage_label' => $this->auditStageLabel($stage),
            'effective_value' => $row->approved_value ?? $row->verified_value ?? $row->inserted_value,
            'effective_date' => $this->displayDateTime($row->approved_date ?? $row->verified_date ?? $row->inserted_date),
            'inserted_date_display' => $this->displayDateTime($row->inserted_date),
            'verified_date_display' => $this->displayDateTime($row->verified_date),
            'approved_date_display' => $this->displayDateTime($row->approved_date),
            'sample_entry_url' => $row->sample_entry_id ? route('vap_samples.show', $row->sample_entry_id) : null,
        ];
    }

    public function auditStage(object $row): string
    {
        if ($row->approved_date) {
            return 'approved';
        }

        return $row->verified_date ? 'verified' : 'inserted';
    }

    public function auditStageLabel(string $stage): string
    {
        return match ($stage) {
            'approved' => 'Aprovado',
            'verified' => 'Verificado',
            default => 'Inserido',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyPendingFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['department_id'] ?? null, fn (Builder $query, $departmentId) => $query->where('analysis.department_id', $departmentId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate(DB::raw('COALESCE(analysis.entry_date, analysis.col_date, analysis.created_at)'), '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate(DB::raw('COALESCE(analysis.entry_date, analysis.col_date, analysis.created_at)'), '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('samples.code', 'like', $like)
                        ->orWhere('lab_codes.code', 'like', $like)
                        ->orWhere('sample_entries.code', 'like', $like)
                        ->orWhere('customers.name', 'like', $like)
                        ->orWhere('products.name', 'like', $like)
                        ->orWhere('profiles.name', 'like', $like)
                        ->orWhere('issued_parameter.code', 'like', $like)
                        ->orWhere('issued_parameter.name', 'like', $like);
                });
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyAuditFilters(Builder $query, array $filters): void
    {
        $stage = $filters['stage'] ?? 'all';

        $query
            ->when($stage === 'inserted', fn (Builder $query) => $query->whereNull('results.verified_date'))
            ->when($stage === 'verified', fn (Builder $query) => $query->whereNotNull('results.verified_date')->whereNull('results.approved_date'))
            ->when($stage === 'approved', fn (Builder $query) => $query->whereNotNull('results.approved_date'))
            ->when($filters['department_id'] ?? null, fn (Builder $query, $departmentId) => $query->where('analysis_context.department_id', $departmentId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate(DB::raw('COALESCE(results.approved_date, results.verified_date, results.inserted_date)'), '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate(DB::raw('COALESCE(results.approved_date, results.verified_date, results.inserted_date)'), '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('samples.code', 'like', $like)
                        ->orWhere('results.code_label', 'like', $like)
                        ->orWhere('sample_entries.code', 'like', $like)
                        ->orWhere('customers.name', 'like', $like)
                        ->orWhere('results.product_label', 'like', $like)
                        ->orWhere('results.parameter_label', 'like', $like)
                        ->orWhere('results.inserted_value', 'like', $like)
                        ->orWhere('results.verified_value', 'like', $like)
                        ->orWhere('results.approved_value', 'like', $like);
                });
            });
    }

    private function displayDate(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : null;
    }

    private function displayDateTime(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y H:i') : null;
    }
}
