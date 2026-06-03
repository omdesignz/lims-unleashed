<?php

namespace App\Notifications;

use App\Models\ProficiencyTest;
use App\Support\WhiteLabelMessageDefaults;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProficiencyTestWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProficiencyTest $proficiencyTest,
        public string $title,
        public string $message,
        public string $tone = 'info',
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $defaults = WhiteLabelMessageDefaults::current();

        return (new MailMessage)
            ->subject($defaults->notificationTitle($this->title))
            ->greeting($defaults->mailGreeting())
            ->line($defaults->notificationEmailIntro($this->message))
            ->line(sprintf(
                '%s - %s (%s)',
                $this->proficiencyTest->round_reference,
                $this->proficiencyTest->name,
                $this->proficiencyTest->provider_name
            ))
            ->action('Abrir ensaios de proficiência', route('proficiency_tests.index'))
            ->line($defaults->notificationEmailOutro())
            ->salutation($defaults->salutationWithSignature());
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => 'proficiency_test',
            'tone' => $this->tone,
            'url' => route('proficiency_tests.index'),
            'proficiency_test_id' => $this->proficiencyTest->id,
            'round_reference' => $this->proficiencyTest->round_reference,
            'status' => $this->proficiencyTest->status,
            'outcome' => $this->proficiencyTest->outcome,
            'deadline_state' => $this->proficiencyTest->deadlineState(),
        ];
    }
}
