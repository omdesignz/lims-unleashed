<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OccurrenceValidation
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(int $labId): array
    {
        $rules = [
            'lab_id' => ['prohibited'],
            'occurrence_no' => ['prohibited'],
            'occurrence_year' => ['prohibited'],
            'seq' => ['prohibited'],
            'date_reported' => ['required', 'date'],
            'issue_description' => ['required', 'string', 'max:255'],
            'reason_for_no_risk_correction_budget' => ['nullable', 'string', 'max:255'],
            'client_acceptance_comments' => ['nullable', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'department_id' => ['bail', 'nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'origin_id' => ['bail', 'nullable', 'integer', Rule::exists('occurrence_origins', 'id')->whereNull('deleted_at')],
            'category_id' => ['bail', 'nullable', 'integer', Rule::exists('occurrence_categories', 'id')->whereNull('deleted_at')],
            'status_id' => ['bail', 'nullable', 'integer', Rule::exists('occurrence_statuses', 'id')->whereNull('deleted_at')],
            'user_id' => ['bail', 'nullable', 'integer', Rule::exists('users', 'id')
                ->whereNull('deleted_at')->where('is_active', true)->whereNotNull('email_verified_at')
                ->where(fn ($query) => $query->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id')))],
        ];

        foreach (['corrective_action', 'analysis', 'effect_corrective_actions', 'cause_corrective_actions', 'obs'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:10000'];
        }

        foreach (['date_resolved', 'notification_date', 'client_process_open_notification_date', 'implementation_date', 'client_process_close_notification_date', 'date_closed'] as $field) {
            $rules[$field] = ['nullable', 'date'];
        }

        foreach (['has_risk_correction_budget', 'has_non_conformity_terms', 'update_risk_matrix', 'client_acceptance', 'was_effective'] as $field) {
            $rules[$field] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        foreach (['category_id', 'department_id', 'user_id', 'status_id', 'origin_id'] as $field) {
            if (array_key_exists($field, $data) && is_array($data[$field])) {
                $data[$field] = $data[$field]['value'] ?? $data[$field];
            }
        }

        foreach (['has_risk_correction_budget', 'has_non_conformity_terms', 'update_risk_matrix'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = false;
            }
        }

        return $data;
    }
}
