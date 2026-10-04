<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return collect($this->resource->toArray())->only([
            'app_name', 'app_version', 'app_slogan', 'app_contact', 'app_email',
            'app_primary_color', 'app_secondary_color', 'app_accent_color',
            'app_theme_preset', 'app_operation_mode', 'app_logo_url',
            'app_login_headline', 'app_login_subheadline', 'app_mail_greeting',
            'app_mail_salutation', 'app_mail_signature_name', 'app_mail_footer', 'app_mail_subcopy',
            'app_notification_sender_alias', 'app_notification_email_intro', 'app_notification_email_outro',
            'app_notification_default_title', 'app_notification_default_message',
            'app_public_key', 'app_nif', 'app_agt_valid_name', 'app_agt_validation_number',
            'app_client_name', 'app_client_nif', 'app_client_address', 'app_client_contact', 'app_client_email',
            'app_client_lab_name', 'app_client_lab_province', 'app_client_lab_director', 'app_client_lab_slogan',
            'app_bank_name', 'app_bank_account_name', 'app_bank_account_number', 'app_bank_iban',
            'app_bank_swift', 'app_bank_details', 'app_document_keywords',
        ])->all();
    }
}
