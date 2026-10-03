<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorksheetAccess;
use App\Services\SampleLaboratoryAccess;
use App\Support\WorksheetValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveWorksheetRequest extends FormRequest
{
    public function authorize(LaboratoryWorksheetAccess $access, SampleLaboratoryAccess $laboratory): bool
    {
        $access->operator($laboratory->activeLabId(), (int) $this->user()?->id,
            $this->route('worksheet') === null ? 'add_worksheets' : 'edit_worksheets');

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(WorksheetValidation $validation): array
    {
        return $validation->rules($this->all());
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return app(WorksheetValidation::class)->attributes();
    }
}
