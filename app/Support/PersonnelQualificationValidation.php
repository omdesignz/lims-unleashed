<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PersonnelQualificationValidation
{
    public const EDITABLE_FIELDS = ['capability', 'department_id', 'authorized_from', 'authorized_until', 'training_completed_at', 'training_reference', 'notes', 'is_active'];

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function validate(array $input): array
    {
        return Validator::make($this->normalize($input), $this->rules(), [], $this->attributes())->validate();
    }

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function normalize(array $input): array
    {
        if (! array_key_exists('personnel_qualifications', $input)) {
            return [];
        }

        $rows = $input['personnel_qualifications'];
        if (! is_array($rows)) {
            return ['personnel_qualifications' => $rows];
        }

        return ['personnel_qualifications' => array_values(array_map(function (mixed $row): mixed {
            if (! is_array($row)) {
                return $row;
            }

            $normalized = [];
            foreach (self::EDITABLE_FIELDS as $field) {
                $value = $row[$field] ?? null;
                if ($field === 'department_id' && is_array($value)) {
                    $value = array_key_exists('value', $value) ? $value['value'] : $value;
                }
                if (is_string($value)) {
                    $value = Str::trim($value);
                    $value = $value === '' ? null : $value;
                }
                $normalized[$field] = $field === 'is_active' ? ($value ?? true) : $value;
            }

            return $normalized;
        }, $rows))];
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return [
            'personnel_qualifications' => ['sometimes', 'array'],
            'personnel_qualifications.*' => ['array'],
            'personnel_qualifications.*.capability' => ['required', 'string', 'max:255'],
            'personnel_qualifications.*.department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'personnel_qualifications.*.authorized_from' => ['nullable', 'date'],
            'personnel_qualifications.*.authorized_until' => ['nullable', 'date', 'after_or_equal:personnel_qualifications.*.authorized_from'],
            'personnel_qualifications.*.training_completed_at' => ['nullable', 'date'],
            'personnel_qualifications.*.training_reference' => ['nullable', 'string', 'max:255'],
            'personnel_qualifications.*.notes' => ['nullable', 'string'],
            'personnel_qualifications.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'personnel_qualifications' => 'qualificações',
            'personnel_qualifications.*' => 'qualificação',
            'personnel_qualifications.*.capability' => 'competência',
            'personnel_qualifications.*.department_id' => 'departamento',
            'personnel_qualifications.*.authorized_from' => 'início da autorização',
            'personnel_qualifications.*.authorized_until' => 'fim da autorização',
            'personnel_qualifications.*.training_completed_at' => 'data da formação',
            'personnel_qualifications.*.training_reference' => 'referência da formação',
            'personnel_qualifications.*.notes' => 'observações',
            'personnel_qualifications.*.is_active' => 'estado da qualificação',
        ];
    }
}
