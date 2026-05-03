<?php

namespace App\Http\Requests;

use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MatrixRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'code' => 'required|min:1|unique:matrixes,code',
                'description' => 'nullable',
                'price' => 'nullable',
                'fixed_price' => 'required',
                'tax_percentage' => 'required',
                'charge_tax' => 'required|boolean',
                'withhold_tax' => 'required|boolean',
                'exemption_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == false; 
                    })
                ], 'exists:tax_exemptions,id',
                'exemption_code' => 'nullable',
                'tax_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == true; 
                    })
                ], 'exists:tax_types,id',
                'profiles' => 'required|array|min:1',
                'profiles.*.profile_id' => 'required|exists:profiles,id',
                'profiles.*.profile' => 'nullable',
            ];
        } else {
            $rules = [
                'code' => 'required|min:1|unique:matrixes,code,' . request()->matrix,
                'description' => 'nullable',
                'price' => 'nullable',
                'fixed_price' => 'required',
                'tax_percentage' => 'required',
                'charge_tax' => 'required|boolean',
                'withhold_tax' => 'required|boolean',
                'exemption_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == false; 
                    })
                ], 'exists:tax_exemptions,id',
                'exemption_code' => 'nullable',
                'tax_id' => [
                    Rule::requiredIf(function () { 
                        return $this->input('charge_tax') == true; 
                    })
                ], 'exists:tax_types,id',
                'profiles' => 'required|array|min:1',
                'profiles.*.profile_id' => 'required|exists:profiles,id',
                'profiles.*.profile' => 'nullable',

            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
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
 * Get the error messages for the defined validation rules.
 *
 * @return array<string, string>
 */
public function messages(): array
{
    return [
        'profiles.*.profile_id.required' => 'É obrigatória a indicação de um valor para o campo perfil',
    ];
}

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        if(request()->boolean('charge_tax')) {

            $this->merge([
                'charge_tax' => request()->boolean('charge_tax') ? 1 : 0,
                'withhold_tax' => request()->boolean('withhold_tax') ? 1 : 0,
                'exemption_id' => null,
                'exemption_code' => null,
                'tax_id' => !is_null(request()->tax_id) ? request()->tax_id['value'] : null,
                'tax_percentage' => !is_null(request()->tax_id) ? request()->tax_id['percent'] : 0, 
                'profiles' => is_null(request()->profiles) ? [] : collect(request()->profiles)->map(function($item) {
                    return [
                        'profile_id' => $item['profile_id']['value'],
                        'profile' => $item['profile_id']['label']
                    ];
                })->toArray()
            ]);

        } else {

            $this->merge([
                'charge_tax' => request()->boolean('charge_tax') ? 1 : 0,
                'withhold_tax' => request()->boolean('withhold_tax') ? 1 : 0,
                'exemption_id' => !is_null(request()->exemption_id) ? request()->exemption_id['value'] : null,
                'exemption_code' => !is_null(request()->exemption_id) ? request()->exemption_id['label'] : null,
                'tax_id' => null,
                'tax_percentage' => 0,
                'profiles' => is_null(request()->profiles) ? [] : collect(request()->profiles)->map(function($item) {
                    return [
                        'profile_id' => $item['profile_id']['value'],
                        'profile' => $item['profile_id']['label']
                    ];
                })->toArray()
            ]);

        }
        
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
                    ->with(['type:id,department_id,name', 'parameters:id'])
                    ->whereIn('id', $profileIds)
                    ->get();

                $profilesWithoutParameters = $profiles
                    ->filter(fn (Profile $profile) => $profile->parameters->isEmpty())
                    ->pluck('name');

                if ($profilesWithoutParameters->isNotEmpty()) {
                    $validator->errors()->add(
                        'profiles',
                        'Todos os perfis da matriz devem possuir parâmetros ativos configurados. Inválidos: ' . $profilesWithoutParameters->implode(', ')
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
                        'A matriz deve reunir perfis do mesmo departamento para manter o escopo analítico controlado.'
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
