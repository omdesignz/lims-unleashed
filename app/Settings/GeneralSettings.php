<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public const MENU_NAME = 'settings';

    public const ABILITIES = ['view', 'edit'];

    public ?string $app_name;

    public ?string $app_version;

    public ?string $app_slogan;

    public ?string $app_contact;

    public ?string $app_email;

    public ?string $app_primary_color;

    public ?string $app_secondary_color;

    public ?string $app_accent_color;

    public ?string $app_theme_preset;

    public ?string $app_operation_mode;

    public ?string $app_logo_url;

    public ?string $app_login_headline;

    public ?string $app_login_subheadline;

    public ?string $app_mail_greeting;

    public ?string $app_mail_salutation;

    public ?string $app_mail_signature_name;

    public ?string $app_mail_footer;

    public ?string $app_mail_subcopy;

    public ?string $app_notification_sender_alias;

    public ?string $app_notification_email_intro;

    public ?string $app_notification_email_outro;

    public ?string $app_notification_default_title;

    public ?string $app_notification_default_message;

    public ?string $app_private_key;

    public ?string $app_public_key;

    public ?string $app_nif;

    public ?string $app_agt_valid_name;

    public ?string $app_agt_validation_number;

    public ?string $app_client_name;

    public ?string $app_client_nif;

    public ?string $app_client_address;

    public ?string $app_client_contact;

    public ?string $app_client_email;

    public ?string $app_client_lab_name;

    public ?string $app_client_lab_province;

    public ?string $app_client_lab_director;

    public ?string $app_client_lab_slogan;

    public ?string $app_bank_name;

    public ?string $app_bank_account_name;

    public ?string $app_bank_account_number;

    public ?string $app_bank_iban;

    public ?string $app_bank_swift;

    public ?string $app_bank_details;

    public ?string $app_document_keywords;

    public static function group(): string
    {
        return 'general';
    }

    public function getAbilities()
    {

        return self::ABILITIES;

    }
}
