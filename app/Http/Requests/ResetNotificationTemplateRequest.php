<?php

namespace App\Http\Requests;

class ResetNotificationTemplateRequest extends UpdateNotificationTemplateRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lab_id' => ['prohibited'], 'key' => ['prohibited'], 'updated_by_id' => ['prohibited'],
        ];
    }
}
