<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorksheetAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveWorksheetsRequest extends FormRequest
{
    public function authorize(LaboratoryWorksheetAccess $access, SampleLaboratoryAccess $laboratory): bool
    {
        $access->operator($laboratory->activeLabId(), (int) $this->user()?->id,
            $this->routeIs('worksheets.restore') ? 'restore_worksheets' : 'delete_worksheets');

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:500'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX, 'distinct'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['recordIds' => 'folhas seleccionadas', 'recordIds.*' => 'folha seleccionada'];
    }
}
