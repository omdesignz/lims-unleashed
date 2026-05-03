<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_login_headline', 'Bem-vindo de volta');
        $this->migrator->add('general.app_login_subheadline', 'Aceda à operação, acompanhe a rastreabilidade e mantenha o laboratório sob controlo.');
        $this->migrator->add('general.app_mail_greeting', 'Olá!');
        $this->migrator->add('general.app_mail_salutation', 'Com os melhores cumprimentos,');
        $this->migrator->add('general.app_mail_signature_name', 'LIMS Unleashed');
        $this->migrator->add('general.app_mail_footer', 'Esta mensagem faz parte da operação e rastreabilidade do seu laboratório.');
        $this->migrator->add('general.app_mail_subcopy', null);
        $this->migrator->add('general.app_notification_sender_alias', 'Equipa LIMS Unleashed');
        $this->migrator->add('general.app_notification_email_intro', 'Recebeu uma nova notificação no sistema.');
        $this->migrator->add('general.app_notification_email_outro', 'Aceda ao sistema para acompanhar o detalhe completo e agir em tempo útil.');
        $this->migrator->add('general.app_notification_default_title', 'Notificação do sistema');
        $this->migrator->add('general.app_notification_default_message', 'Existe uma atualização importante disponível para si no sistema.');
    }
};
