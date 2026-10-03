<?php

namespace App\Http\Requests;

use App\Services\RatingLaboratoryAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRatingRequest extends FormRequest
{
    public function authorize(RatingLaboratoryAccess $access, SampleLaboratoryAccess $laboratory): bool
    {
        if ($this->routeIs('portal.rating.store')) {
            if (! $this->user('portal')) {
                return false;
            }
            $access->invitation((string) $this->route('invitation'), (int) $this->user('portal')->id);

            return true;
        }
        if (! $this->user()) {
            return false;
        }
        $labId = $laboratory->activeLabId();
        $access->member($labId, (int) $this->user()->id);
        $id = filter_var($this->route('rateableId', '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        abort_if($id === false, 404);
        $access->subject($labId, (string) $this->route('rateableType'), $id);

        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'criteria' => ['required', 'array', 'min:1', 'max:100'],
            'criteria.*' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
            'lab_id' => ['prohibited'],
            'rater_id' => ['prohibited'],
            'rater_type' => ['prohibited'],
            'rating_request_id' => ['prohibited'],
            'rateable_type' => ['prohibited'],
            'rateable_id' => ['prohibited'],
            'channel' => ['prohibited'],
        ];
    }
}
