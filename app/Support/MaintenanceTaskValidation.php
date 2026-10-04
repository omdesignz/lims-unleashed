<?php

namespace App\Support;

use App\Models\InventoryItem;
use App\Models\MaintenanceTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class MaintenanceTaskValidation
{
    public const EDITABLE_FIELDS = [
        'name', 'description', 'category_id', 'equipment_id', 'due_date',
        'periodicity', 'periodicity_unit', 'cost', 'executed_by_supplier',
        'supplier_id', 'obs', 'is_planned', 'is_executed', 'acceptance_criteria',
        'result', 'range', 'calibration_points', 'calibration_status', 'calibration_certificate_no',
    ];

    /** @return array<string, array<int, mixed>> */
    public static function rules(int $labId, ?MaintenanceTask $task = null): array
    {
        return [
            'lab_id' => ['prohibited'],
            'maintenance_task_no' => ['prohibited'],
            'maintenance_task_year' => ['prohibited'],
            'seq' => ['prohibited'],
            'previous_date' => ['prohibited'],
            'next_date' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['bail', 'required', 'integer', Rule::exists('maintenance_categories', 'id')->when(! $task, fn ($rule) => $rule->whereNull('deleted_at'))
                ->where(fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('lab_id')->orWhere('lab_id', $labId))), ...($task ? [Rule::in([$task->category_id])] : [])],
            'equipment_id' => ['bail', 'required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')
                ->where(fn (Builder $query): Builder => $query->whereIn('id', InventoryItem::equipment()->select('id')))],
            'due_date' => ['required', 'date'],
            'periodicity' => ['required_with:periodicity_unit', 'nullable', 'integer', 'min:1', 'max:10000'],
            'periodicity_unit' => ['required_with:periodicity', 'nullable', Rule::in(['hours', 'days', 'weeks', 'months', 'years'])],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'executed_by_supplier' => ['required', 'boolean'],
            'supplier_id' => ['bail', 'required_if:executed_by_supplier,true', 'nullable', 'integer', Rule::exists('i_suppliers', 'id')->whereNull('deleted_at')],
            'obs' => ['nullable', 'string', 'max:5000'],
            'is_planned' => ['required', 'boolean'],
            'is_executed' => ['required', 'boolean'],
            'acceptance_criteria' => ['nullable', 'string', 'max:255'],
            'result' => ['required_if:is_executed,true', 'nullable', 'string', 'max:10000'],
            'range' => ['nullable', 'string', 'max:255'],
            'calibration_points' => ['nullable', 'string', 'max:10000'],
            'calibration_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'calibration_certificate_no' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeCurrentState(array $data, ?MaintenanceTask $task = null): array
    {
        $current = $task?->only(self::EDITABLE_FIELDS) ?? [
            'executed_by_supplier' => false, 'is_planned' => false, 'is_executed' => false,
        ];

        $merged = array_replace($current, $data);
        if (array_key_exists('cost', $merged) && $merged['cost'] === null) {
            $merged['cost'] = 0;
        }

        return $merged;
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'result.required_if' => 'Registe um resultado antes de concluir a tarefa.',
            'supplier_id.required_if' => 'Seleccione o fornecedor responsável pela execução externa.',
            'periodicity.required_with' => 'Indique a quantidade da periodicidade ou remova a unidade.',
            'periodicity_unit.required_with' => 'Seleccione a unidade da periodicidade ou remova a quantidade.',
            'category_id.in' => 'A categoria faz parte do número emitido e não pode ser alterada.',
        ];
    }
}
