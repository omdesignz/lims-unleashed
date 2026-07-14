<?php

namespace App\Http\Requests;

use App\Models\Profile;
use App\Models\TaxExemption;
use App\Models\TaxType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:1',
                Rule::unique('matrixes', 'code')->ignore($this->route('matrix')),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'fixed_price' => ['required', 'numeric', 'min:0'],
            'tax_percentage' => ['required', 'numeric', 'min:0'],
            'charge_tax' => ['required', 'boolean'],
            'withhold_tax' => ['required', 'boolean'],
            'exemption_id' => [
                Rule::requiredIf(fn (): bool => ! $this->boolean('charge_tax')),
                'nullable',
                Rule::exists('tax_exemptions', 'id')->whereNull('deleted_at'),
            ],
            'exemption_code' => ['nullable', 'string'],
            'tax_id' => [
                Rule::requiredIf(fn (): bool => $this->boolean('charge_tax')),
                'nullable',
                Rule::exists('tax_types', 'id')->whereNull('deleted_at'),
            ],
            'profiles' => ['required', 'array', 'min:1'],
            'profiles.*.profile_id' => [
                'required',
                Rule::exists('profiles', 'id')->whereNull('deleted_at'),
            ],
            'profiles.*.profile' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => trans('gestlab.general.labels.matrixes.code'),
            'description' => trans('gestlab.general.labels.matrixes.description'),
            'price' => trans('gestlab.general.labels.matrixes.price'),
            'tax_id' => trans('gestlab.general.labels.matrixes.tax_id'),
            'charge_tax' => trans('gestlab.general.labels.matrixes.charge_tax'),
            'withhold_tax' => trans('gestlab.general.labels.matrixes.withhold_tax'),
            'exemption_id' => trans('gestlab.general.labels.matrixes.exemption_id'),
            'fixed_price' => trans('gestlab.general.labels.matrixes.fixed_price'),
            'profiles' => trans('gestlab.general.labels.matrixes.profiles'),
            'profiles.*.profile_id' => trans('gestlab.general.labels.matrixes.profile_id'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profiles.*.profile_id.required' => 'É obrigatória a indicação de um valor para o campo perfil',
        ];
    }

    public function prepareForValidation(): void
    {
        $chargeTax = $this->boolean('charge_tax');
        $taxType = $this->input('tax_id');
        $exemption = $this->input('exemption_id');
        $profiles = $this->input('profiles');
        $taxId = $chargeTax ? $this->optionValue($taxType) : null;
        $exemptionId = $chargeTax ? null : $this->optionValue($exemption);
        $profileRows = is_array($profiles)
            ? collect($profiles)->map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                return ['profile_id' => $this->optionValue(data_get($item, 'profile_id'))];
            })
            : collect();
        $profileIds = $profileRows
            ->pluck('profile_id')
            ->filter(fn (mixed $profileId): bool => is_numeric($profileId))
            ->map(fn (mixed $profileId): int => (int) $profileId)
            ->unique()
            ->values();
        $catalogProfiles = Profile::query()
            ->with('parameters')
            ->whereIn('id', $profileIds)
            ->get()
            ->keyBy('id');
        $catalogTaxType = $taxId ? TaxType::query()->find($taxId) : null;
        $catalogExemption = $exemptionId ? TaxExemption::query()->find($exemptionId) : null;

        $this->merge([
            'charge_tax' => $chargeTax,
            'withhold_tax' => $this->boolean('withhold_tax'),
            'price' => (float) $catalogProfiles->sum->price_based_on_parameters,
            'exemption_id' => $exemptionId,
            'exemption_code' => $catalogExemption?->code,
            'tax_id' => $taxId,
            'tax_percentage' => (float) ($catalogTaxType?->percent ?? 0),
            'profiles' => is_array($profiles) ? $profileRows->map(function (mixed $item) use ($catalogProfiles): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                $profileId = data_get($item, 'profile_id');

                return [
                    'profile_id' => $profileId,
                    'profile' => $catalogProfiles->get((int) $profileId)?->name,
                ];
            })->all() : $profiles,
        ]);
    }

    private function optionValue(mixed $option): mixed
    {
        if (is_array($option) || is_object($option)) {
            return data_get($option, 'value');
        }

        return $option === '' ? null : $option;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $profileIds = collect($this->input('profiles', []))
                    ->pluck('profile_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($profileIds->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('profiles', 'A matriz não pode repetir o mesmo perfil.');
                }

                if ($profileIds->isEmpty()) {
                    return;
                }

                $profiles = Profile::query()
                    ->with(['type:id,department_id,name', 'parameters:id,active'])
                    ->whereIn('id', $profileIds)
                    ->get();

                $profilesWithoutActiveParameters = $profiles
                    ->filter(fn (Profile $profile) => $profile->parameters->where('active', true)->isEmpty())
                    ->pluck('name');

                if ($profilesWithoutActiveParameters->isNotEmpty()) {
                    $validator->errors()->add(
                        'profiles',
                        'Todos os perfis da matriz devem possuir parâmetros activos configurados. Inválidos: '.$profilesWithoutActiveParameters->implode(', ')
                    );
                }

                $departmentIds = $profiles
                    ->pluck('type.department_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($departmentIds->count() > 1) {
                    $validator->errors()->add(
                        'profiles',
                        'A matriz deve reunir perfis do mesmo departamento para manter o âmbito analítico controlado.'
                    );
                }

                if ($profiles->contains(fn (Profile $profile) => ! $profile->type?->department_id)) {
                    $validator->errors()->add(
                        'profiles',
                        'Todos os perfis da matriz devem estar ligados a uma categoria analítica com departamento definido.'
                    );
                }
            },
        ];
    }
}
