<?php

namespace App\Http\Requests;

use App\Services\RatingLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssuePortalRatingInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('add_ratings') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'string', 'email', 'max:255'],
            'rateable_type' => ['required', Rule::in(RatingLaboratoryAccess::PORTAL_TYPES)],
            'rateable_id' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'lab_id' => ['prohibited'],
            'issued_by_id' => ['prohibited'],
            'rater_id' => ['prohibited'],
            'rater_type' => ['prohibited'],
            'status' => ['prohibited'],
            'invitation' => ['prohibited'],
            'criteria_snapshot' => ['prohibited'],
            'expires_at' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['recipient_email' => 'email do destinatário', 'rateable_type' => 'processo', 'rateable_id' => 'registo'];
    }
}
