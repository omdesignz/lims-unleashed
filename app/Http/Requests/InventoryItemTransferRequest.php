<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemTransferRequest extends FormRequest
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
                'qty' => 'required|integer',
                'sent_date' => 'required|date',
                'received_date' => 'nullable|date',
                'obs' => 'nullable|string',
                'item_id' => 'required|exists:i_items,id',
                'source_id' => 'required|exists:i_warehouses,id',
                'destination_id' => 'required|exists:i_warehouses,id',
            ];
        } else {
            $rules = [
                'qty' => 'required|integer',
                'sent_date' => 'required|date',
                'received_date' => 'nullable|date',
                'obs' => 'nullable|string',
                'item_id' => 'required|exists:i_items,id',
                'source_id' => 'required|exists:i_warehouses,id',
                'destination_id' => 'required|exists:i_warehouses,id',
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
            'qty' => trans('gestlab.general.labels.itransfers.qty'),
            'sent_date' => trans('gestlab.general.labels.itransfers.sent_date'),
            'received_date' => trans('gestlab.general.labels.itransfers.received_date'),
            'obs' => trans('gestlab.general.labels.itransfers.obs'),
            'item_id' => trans('gestlab.general.labels.itransfers.item_id'),
            'source_id' => trans('gestlab.general.labels.itransfers.source_id'),
            'destination_id' => trans('gestlab.general.labels.itransfers.destination_id'),
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
        // dd(request()->all());
        $this->merge([
            'item_id' => !is_null(request()->item_id) ? request()->item_id['value'] : null,
            'source_id' => !is_null(request()->source_id) ? request()->source_id['value'] : null,
            'destination_id' => !is_null(request()->destination_id) ? request()->destination_id['value'] : null,
        ]);
            
    }
}
