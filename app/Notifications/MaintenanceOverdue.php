<?php

namespace App\Notifications;

use App\Models\MaintenanceTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MaintenanceOverdue extends Notification
{
    use Queueable;

    public $tasks;

    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Alerta de manutenção em atraso')
            ->greeting('Atenção imediata necessária')
            ->line('Existem ' . $this->tasks->count() . ' tarefas de manutenção em atraso.')
            ->line('Estas tarefas exigem ação prioritária:')
            ->markdown('emails.maintenance.overdue', [
                'tasks' => $this->tasks,
            ])
            ->action('Rever tarefas em atraso', route('vap-maintenance.dashboard', ['status' => 'overdue']))
            ->error();
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'maintenance_overdue',
            'message' => 'Existem ' . $this->tasks->count() . ' tarefas de manutenção em atraso.',
            'task_count' => $this->tasks->count(),
            'url' => route('vap-maintenance.dashboard', ['status' => 'overdue']),
            'priority' => 'high',
        ];
    }
}
