<?php

namespace App\Http\Requests;

use App\Support\ExportHubCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportHubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dataset = (string) $this->input('dataset');

        if ($dataset === 'overview') {
            return $this->canAccessHub();
        }

        $catalog = app(ExportHubCatalog::class);

        return $catalog->isDirect($dataset)
            && ($this->user()?->can($catalog->get($dataset)['permission']) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dataset' => ['required', Rule::in(['overview', ...app(ExportHubCatalog::class)->directKeys()])],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['all', 'active', 'archived'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'log_name' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:255'],
            'causer_id' => ['nullable', 'integer', 'exists:users,id'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'property' => ['nullable', 'string', 'max:120'],
            'batch_uuid' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'integer', 'exists:customer_categories,id'],
            'analysis_category_id' => ['nullable', 'integer', 'exists:analysis_categories,id'],
            'request_category_id' => ['nullable', 'integer', 'exists:customer_request_categories,id'],
            'occurrence_category_id' => ['nullable', 'integer', 'exists:occurrence_categories,id'],
            'occurrence_status_id' => ['nullable', 'integer', 'exists:occurrence_statuses,id'],
            'occurrence_origin_id' => ['nullable', 'integer', 'exists:occurrence_origins,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'province' => ['nullable', 'string', 'max:255'],
            'has_primary_site' => ['required', Rule::in(['all', 'yes', 'no'])],
            'matrix_id' => ['nullable', 'integer', 'exists:matrixes,id'],
            'tax_status' => ['required', Rule::in(['all', 'taxable', 'exempt'])],
            'withholding' => ['required', Rule::in(['all', 'yes', 'no'])],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'payment_type_id' => ['nullable', 'integer', 'exists:payment_categories,id'],
            'transport_type_id' => ['nullable', 'integer', 'exists:trans_categories,id'],
            'payment_status' => ['required', Rule::in(['all', 'paid', 'unpaid', 'canceled'])],
            'converted' => ['required', Rule::in(['all', 'yes', 'no'])],
            'invoiced' => ['required', Rule::in(['all', 'yes', 'no'])],
            'validation_status' => ['required', Rule::in(['all', 'validated', 'pending'])],
            'enabled' => ['required', Rule::in(['all', 'yes', 'no'])],
            'result_type' => ['nullable', 'string', 'max:60'],
            'reason' => ['nullable', Rule::in(['R', 'A'])],
            'workflow_status' => ['nullable', 'string', 'max:60'],
            'priority' => ['nullable', 'string', 'max:60'],
            'min_total' => ['nullable', 'numeric', 'min:0'],
            'max_total' => ['nullable', 'numeric', 'gte:min_total'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dataset = $this->input('dataset') ?: $this->defaultDataset();

        $this->merge([
            'dataset' => $dataset,
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'status' => $this->input('status', $dataset === 'activity_log' ? 'all' : 'active'),
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'log_name' => $this->filled('log_name') ? $this->input('log_name') : null,
            'event' => $this->filled('event') ? $this->input('event') : null,
            'causer_id' => $this->filled('causer_id') ? $this->input('causer_id') : null,
            'subject_type' => $this->filled('subject_type') ? $this->input('subject_type') : null,
            'subject_id' => $this->filled('subject_id') ? $this->input('subject_id') : null,
            'property' => $this->filled('property') ? trim((string) $this->input('property')) : null,
            'batch_uuid' => $this->filled('batch_uuid') ? $this->input('batch_uuid') : null,
            'category_id' => $this->filled('category_id') ? $this->input('category_id') : null,
            'analysis_category_id' => $this->filled('analysis_category_id') ? $this->input('analysis_category_id') : null,
            'request_category_id' => $this->filled('request_category_id') ? $this->input('request_category_id') : null,
            'occurrence_category_id' => $this->filled('occurrence_category_id') ? $this->input('occurrence_category_id') : null,
            'occurrence_status_id' => $this->filled('occurrence_status_id') ? $this->input('occurrence_status_id') : null,
            'occurrence_origin_id' => $this->filled('occurrence_origin_id') ? $this->input('occurrence_origin_id') : null,
            'department_id' => $this->filled('department_id') ? $this->input('department_id') : null,
            'province' => $this->filled('province') ? $this->input('province') : null,
            'has_primary_site' => $this->input('has_primary_site', 'all'),
            'matrix_id' => $this->filled('matrix_id') ? $this->input('matrix_id') : null,
            'tax_status' => $this->input('tax_status', 'all'),
            'withholding' => $this->input('withholding', 'all'),
            'min_price' => $this->filled('min_price') ? $this->input('min_price') : null,
            'max_price' => $this->filled('max_price') ? $this->input('max_price') : null,
            'customer_id' => $this->filled('customer_id') ? $this->input('customer_id') : null,
            'warehouse_id' => $this->filled('warehouse_id') ? $this->input('warehouse_id') : null,
            'product_id' => $this->filled('product_id') ? $this->input('product_id') : null,
            'payment_type_id' => $this->filled('payment_type_id') ? $this->input('payment_type_id') : null,
            'transport_type_id' => $this->filled('transport_type_id') ? $this->input('transport_type_id') : null,
            'payment_status' => $this->input('payment_status', 'all'),
            'converted' => $this->input('converted', 'all'),
            'invoiced' => $this->input('invoiced', 'all'),
            'validation_status' => $this->input('validation_status', 'all'),
            'enabled' => $this->input('enabled', 'all'),
            'result_type' => $this->filled('result_type') ? $this->input('result_type') : null,
            'reason' => $this->filled('reason') ? $this->input('reason') : null,
            'workflow_status' => $this->filled('workflow_status') ? $this->input('workflow_status') : null,
            'priority' => $this->filled('priority') ? $this->input('priority') : null,
            'min_total' => $this->filled('min_total') ? $this->input('min_total') : null,
            'max_total' => $this->filled('max_total') ? $this->input('max_total') : null,
        ]);
    }

    private function defaultDataset(): string
    {
        return $this->user() ? (app(ExportHubCatalog::class)->firstPermitted($this->user()) ?? 'overview') : 'overview';
    }

    private function canAccessHub(): bool
    {
        $permissions = collect(app(ExportHubCatalog::class)->directDatasets())->pluck('permission')
            ->push('view_analysis', 'view_results', 'view_iitems', 'view_maintenance_tasks', 'view_occurrences', 'view_samples');

        return $permissions->contains(fn (string $permission): bool => $this->user()?->can($permission) ?? false);
    }
}
