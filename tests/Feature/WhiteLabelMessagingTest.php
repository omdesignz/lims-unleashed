<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\GlobalNotification;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WhiteLabelMessagingTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for white-label messaging testing.');

        return $admin;
    }

    /**
     * @return array<string, string|null>
     */
    private function messageSettingSnapshot(GeneralSettings $settings): array
    {
        return [
            'app_logo_url' => $settings->app_logo_url,
            'app_login_headline' => $settings->app_login_headline,
            'app_login_subheadline' => $settings->app_login_subheadline,
            'app_mail_greeting' => $settings->app_mail_greeting,
            'app_mail_salutation' => $settings->app_mail_salutation,
            'app_mail_signature_name' => $settings->app_mail_signature_name,
            'app_mail_footer' => $settings->app_mail_footer,
            'app_mail_subcopy' => $settings->app_mail_subcopy,
            'app_notification_sender_alias' => $settings->app_notification_sender_alias,
            'app_notification_email_intro' => $settings->app_notification_email_intro,
            'app_notification_email_outro' => $settings->app_notification_email_outro,
            'app_notification_default_title' => $settings->app_notification_default_title,
            'app_notification_default_message' => $settings->app_notification_default_message,
        ];
    }

    public function test_admin_can_update_white_label_message_settings(): void
    {
        $admin = $this->verifiedAdmin();
        $settings = app(GeneralSettings::class);
        $original = $this->messageSettingSnapshot($settings);

        $payload = [
            'app_logo_url' => 'https://cdn.example.test/brand/logo-whitelabel.svg',
            'app_login_headline' => 'Pulse operacional do laboratório',
            'app_login_subheadline' => 'Entre com a equipa certa, mantenha rastreabilidade e governe a operação a partir de um único cockpit.',
            'app_mail_greeting' => 'Olá equipa,',
            'app_mail_salutation' => 'Com rigor operacional,',
            'app_mail_signature_name' => 'Direção da Plataforma',
            'app_mail_footer' => 'Esta comunicação faz parte do sistema de gestão do laboratório.',
            'app_mail_subcopy' => 'Se o botão não abrir, copie o endereço completo abaixo para o seu navegador.',
            'app_notification_sender_alias' => 'Centro de Comando LIMS',
            'app_notification_email_intro' => 'Foi registada uma atualização operacional que requer a sua atenção.',
            'app_notification_email_outro' => 'Entre na plataforma para validar o contexto completo e executar a próxima ação.',
            'app_notification_default_title' => 'Pulso operacional',
            'app_notification_default_message' => 'Existe uma atualização crítica na sua fila de trabalho.',
        ];

        try {
            $response = $this->actingAs($admin)->post(route('generalsettings.update'), $payload);

            $response->assertRedirect();

            $settings = app(GeneralSettings::class)->refresh();

            foreach ($payload as $key => $value) {
                $this->assertSame($value, $settings->{$key}, "Failed asserting that [{$key}] was updated.");
            }
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_login_page_exposes_white_label_branding_settings(): void
    {
        $settings = app(GeneralSettings::class);
        $original = $this->messageSettingSnapshot($settings);

        try {
            $settings->fill([
                'app_logo_url' => 'https://cdn.example.test/brand/login.svg',
                'app_login_headline' => 'Cockpit de conformidade',
                'app_login_subheadline' => 'Coordene amostras, resultados e notificações com uma voz única.',
            ]);
            $settings->save();
            $settings->refresh();

            $response = $this->get(route('login'));

            $response
                ->assertSuccessful()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Auth/Login')
                    ->where('settings.logo_url', 'https://cdn.example.test/brand/login.svg')
                    ->where('settings.login_headline', 'Cockpit de conformidade')
                    ->where('settings.login_subheadline', 'Coordene amostras, resultados e notificações com uma voz única.')
                );
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_admin_notification_create_page_receives_white_label_defaults(): void
    {
        $admin = $this->verifiedAdmin();
        $settings = app(GeneralSettings::class);
        $original = $this->messageSettingSnapshot($settings);

        try {
            $settings->fill([
                'app_notification_sender_alias' => 'Mesa de Controlo',
                'app_notification_default_title' => 'Resumo diário',
                'app_notification_default_message' => 'Tem uma nova prioridade operacional para rever.',
            ]);
            $settings->save();
            $settings->refresh();

            $response = $this->actingAs($admin)->get(route('admin.notifications.create'));

            $response
                ->assertSuccessful()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Notifications/Create')
                    ->where('settings.notification_sender_alias', 'Mesa de Controlo')
                    ->where('settings.notification_default_title', 'Resumo diário')
                    ->where('settings.notification_default_message', 'Tem uma nova prioridade operacional para rever.')
                );
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }

    public function test_global_notification_uses_white_label_defaults_for_mail_and_database_payloads(): void
    {
        $recipient = $this->verifiedAdmin();
        $sender = User::query()->whereKeyNot($recipient->id)->firstOrFail();
        $settings = app(GeneralSettings::class);
        $original = $this->messageSettingSnapshot($settings);

        try {
            $settings->fill([
                'app_mail_greeting' => 'Olá equipa,',
                'app_mail_salutation' => 'Com rigor operacional,',
                'app_mail_signature_name' => 'Direção da Plataforma',
                'app_notification_sender_alias' => 'Centro de Comando LIMS',
                'app_notification_email_intro' => 'Foi registada uma atualização operacional que requer a sua atenção.',
                'app_notification_email_outro' => 'Entre na plataforma para validar o contexto completo e executar a próxima ação.',
                'app_notification_default_title' => 'Pulso operacional',
                'app_notification_default_message' => 'Existe uma atualização crítica na sua fila de trabalho.',
            ]);
            $settings->save();
            $settings->refresh();

            $notification = new GlobalNotification(null, null, $sender);
            $mailMessage = $notification->toMail($recipient);
            $databasePayload = $notification->toDatabase($recipient);

            $this->assertSame('Pulso operacional', $mailMessage->subject);
            $this->assertSame('Olá equipa,', $mailMessage->greeting);
            $this->assertSame(
                ['Foi registada uma atualização operacional que requer a sua atenção.', 'Existe uma atualização crítica na sua fila de trabalho.'],
                $mailMessage->introLines
            );
            $this->assertSame(
                ['Entre na plataforma para validar o contexto completo e executar a próxima ação.'],
                $mailMessage->outroLines
            );
            $this->assertSame("Com rigor operacional,\nDireção da Plataforma", $mailMessage->salutation);
            $this->assertSame('Pulso operacional', $databasePayload['title']);
            $this->assertSame('Existe uma atualização crítica na sua fila de trabalho.', $databasePayload['message']);
            $this->assertSame('Centro de Comando LIMS', $databasePayload['sender_name']);
        } finally {
            $settings = app(GeneralSettings::class);
            $settings->fill($original);
            $settings->save();
        }
    }
}
