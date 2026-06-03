<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_settings') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'app_slogan' => ['nullable', 'string', 'max:255'],
            'app_contact' => ['nullable', 'string', 'max:50'],
            'app_email' => ['nullable', 'email', 'max:255'],
            'app_primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'app_secondary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'app_accent_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'app_theme_preset' => ['nullable', 'string', 'in:corporate,clinical,executive,vibrant'],
            'app_operation_mode' => ['nullable', 'string', 'in:client_only,internal_only,hybrid'],
            'app_logo_url' => ['nullable', 'string', 'max:500'],
            'app_login_headline' => ['nullable', 'string', 'max:255'],
            'app_login_subheadline' => ['nullable', 'string', 'max:500'],
            'app_mail_greeting' => ['nullable', 'string', 'max:255'],
            'app_mail_salutation' => ['nullable', 'string', 'max:500'],
            'app_mail_signature_name' => ['nullable', 'string', 'max:255'],
            'app_mail_footer' => ['nullable', 'string', 'max:500'],
            'app_mail_subcopy' => ['nullable', 'string', 'max:1000'],
            'app_notification_sender_alias' => ['nullable', 'string', 'max:255'],
            'app_notification_email_intro' => ['nullable', 'string', 'max:500'],
            'app_notification_email_outro' => ['nullable', 'string', 'max:500'],
            'app_notification_default_title' => ['nullable', 'string', 'max:255'],
            'app_notification_default_message' => ['nullable', 'string', 'max:500'],
            'app_private_key' => ['nullable', 'string'],
            'app_public_key' => ['nullable', 'string'],
            'app_nif' => ['nullable', 'string', 'max:60'],
            'app_agt_valid_name' => ['nullable', 'string', 'max:255'],
            'app_agt_validation_number' => ['nullable', 'string', 'max:255'],
            'app_client_name' => ['nullable', 'string', 'max:255'],
            'app_client_nif' => ['nullable', 'string', 'max:60'],
            'app_client_address' => ['nullable', 'string', 'max:255'],
            'app_client_contact' => ['nullable', 'string', 'max:50'],
            'app_client_email' => ['nullable', 'email', 'max:255'],
            'app_client_lab_name' => ['nullable', 'string', 'max:255'],
            'app_client_lab_province' => ['nullable', 'string', 'max:255'],
            'app_client_lab_director' => ['nullable', 'string', 'max:255'],
            'app_client_lab_slogan' => ['nullable', 'string', 'max:255'],
            'app_bank_name' => ['nullable', 'string', 'max:255'],
            'app_bank_account_name' => ['nullable', 'string', 'max:255'],
            'app_bank_account_number' => ['nullable', 'string', 'max:255'],
            'app_bank_iban' => ['nullable', 'string', 'max:255'],
            'app_bank_swift' => ['nullable', 'string', 'max:255'],
            'app_bank_details' => ['nullable', 'string', 'max:1000'],
            'app_document_keywords' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'app_agt_valid_name' => 'nome AGT válido',
            'app_agt_validation_number' => 'número de validação AGT',
            'app_private_key' => 'chave privada',
            'app_public_key' => 'chave pública',
            'app_theme_preset' => 'preset visual',
            'app_operation_mode' => 'modo operacional',
            'app_logo_url' => 'logótipo',
            'app_login_headline' => 'headline do login',
            'app_login_subheadline' => 'subheadline do login',
            'app_mail_greeting' => 'saudação do email',
            'app_mail_salutation' => 'assinatura do email',
            'app_mail_signature_name' => 'nome da assinatura do email',
            'app_mail_footer' => 'rodapé do email',
            'app_mail_subcopy' => 'texto auxiliar do email',
            'app_notification_sender_alias' => 'nome do remetente das notificações',
            'app_notification_email_intro' => 'introdução do email de notificação',
            'app_notification_email_outro' => 'fecho do email de notificação',
            'app_notification_default_title' => 'título padrão da notificação',
            'app_notification_default_message' => 'mensagem padrão da notificação',
            'app_bank_name' => 'banco',
            'app_bank_account_name' => 'titular da conta',
            'app_bank_account_number' => 'número de conta',
            'app_bank_iban' => 'IBAN',
            'app_bank_swift' => 'SWIFT/BIC',
            'app_bank_details' => 'observações bancárias',
            'app_document_keywords' => 'palavras-chave documentais',
        ];
    }
}
