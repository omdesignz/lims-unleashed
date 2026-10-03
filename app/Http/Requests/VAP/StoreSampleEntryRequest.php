<?php

namespace App\Http\Requests\VAP;

use App\Services\SampleLaboratoryAccess;
use App\Support\SampleEntryValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreSampleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('sampleEntry') ? 'edit_samples' : 'add_samples') ?? false;
    }

    public function rules(): array
    {
        return app(SampleEntryValidation::class)->rules(
            $this->all(), app(SampleLaboratoryAccess::class)->activeLabId(), $this->route('sampleEntry')
        );
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return app(SampleEntryValidation::class)->attributes();
    }

    public function after(): array
    {
        return app(SampleEntryValidation::class)->after($this->all(), $this->route('sampleEntry'));
    }
}
