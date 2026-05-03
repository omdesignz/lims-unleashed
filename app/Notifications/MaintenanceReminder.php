<?php

namespace App\Notifications;

use App\Models\MaintenanceTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MaintenanceReminder extends Notification
{
    use Queueable;

    public $tasks;
    public $daysThreshold;

    public function __construct($tasks, $daysThreshold)
    {
        $this->tasks = $tasks;
        $this->daysThreshold = $daysThreshold;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Lembrete de manutenção programada')
            ->greeting('Olá, ' . $notifiable->name . '.')
            ->line('Existem ' . $this->tasks->count() . ' tarefas de manutenção com vencimento nos próximos ' . $this->daysThreshold . ' dias.')
            ->action('Abrir painel de manutenção', route('vap-maintenance.dashboard'))
            ->line('Revise o plano e tome as ações necessárias.')
            ->markdown('emails.maintenance.reminder', [
                'tasks' => $this->tasks,
                'daysThreshold' => $this->daysThreshold,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'maintenance_reminder',
            'message' => 'Existem ' . $this->tasks->count() . ' tarefas de manutenção com vencimento nos próximos ' . $this->daysThreshold . ' dias.',
            'task_count' => $this->tasks->count(),
            'days_threshold' => $this->daysThreshold,
            'url' => route('vap-maintenance.dashboard'),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'maintenance_reminder',
            'message' => 'Existem ' . $this->tasks->count() . ' tarefas de manutenção com vencimento nos próximos ' . $this->daysThreshold . ' dias.',
            'task_count' => $this->tasks->count(),
            'timestamp' => now(),
        ]);
    }
}
