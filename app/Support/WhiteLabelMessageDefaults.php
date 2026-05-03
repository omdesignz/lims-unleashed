<?php

namespace App\Support;

use App\Settings\GeneralSettings;

class WhiteLabelMessageDefaults
{
    public function __construct(private readonly GeneralSettings $settings)
    {
    }

    public static function current(): self
    {
        return new self(app(GeneralSettings::class));
    }

    public function appName(): string
    {
        return $this->settings->app_name ?: config('app.name', 'LIMS Unleashed');
    }

    public function senderAlias(?string $fallback = null): string
    {
        return $this->settings->app_notification_sender_alias
            ?: $fallback
            ?: $this->appName();
    }

    public function mailGreeting(?string $fallback = null): string
    {
        return $this->settings->app_mail_greeting
            ?: $fallback
            ?: 'Olá!';
    }

    public function mailFooter(?string $fallback = null): ?string
    {
        return $this->settings->app_mail_footer
            ?: $fallback;
    }

    public function mailSubcopy(?string $fallback = null): ?string
    {
        return $this->settings->app_mail_subcopy
            ?: $fallback;
    }

    public function mailSignatureName(?string $fallback = null): string
    {
        return $this->settings->app_mail_signature_name
            ?: $fallback
            ?: $this->appName();
    }

    public function mailSalutation(?string $fallback = null): string
    {
        return $this->settings->app_mail_salutation
            ?: $fallback
            ?: 'Com os melhores cumprimentos,';
    }

    public function salutationWithSignature(?string $salutationFallback = null, ?string $signatureFallback = null): string
    {
        return trim($this->mailSalutation($salutationFallback)."\n".$this->mailSignatureName($signatureFallback));
    }

    public function notificationTitle(?string $fallback = null): string
    {
        return $this->settings->app_notification_default_title
            ?: $fallback
            ?: 'Notificação do sistema';
    }

    public function notificationMessage(?string $fallback = null): string
    {
        return $this->settings->app_notification_default_message
            ?: $fallback
            ?: 'Existe uma atualização importante disponível para si no sistema.';
    }

    public function notificationEmailIntro(?string $fallback = null): string
    {
        return $this->settings->app_notification_email_intro
            ?: $fallback
            ?: 'Recebeu uma nova notificação no sistema.';
    }

    public function notificationEmailOutro(?string $fallback = null): string
    {
        return $this->settings->app_notification_email_outro
            ?: $fallback
            ?: 'Aceda ao sistema para acompanhar o detalhe completo e agir em tempo útil.';
    }
}
