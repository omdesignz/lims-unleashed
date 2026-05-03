<?php

namespace App\Notifications;

use App\Models\MaintenanceTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MaintenanceCompleted extends Notification
{
    use Queueable;

    public $task;

    public function __construct(MaintenanceTask $task)
    {
        $this->task = $task;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Tarefa de manutenção concluída: ' . $this->task->name)
            ->greeting('Conclusão registada')
            ->line('A seguinte tarefa de manutenção foi concluída:')
            ->line('**Tarefa:** ' . $this->task->name)
            ->line('**Equipamento:** ' . $this->task->equipment->name)
            ->line('**Concluída em:** ' . ($this->task?->due_date?->format('d/m/Y') ?? 'N/D'))
            ->line('**Próxima data:** ' . ($this->task->next_date ? $this->task->next_date->format('d/m/Y') : 'Sem reagendamento'))
            ->action('Ver tarefa', route('vap-maintenance.tasks.show', $this->task))
            ->success();
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'maintenance_completed',
            'message' => 'A tarefa de manutenção "' . $this->task->name . '" foi concluída.',
            'task_id' => $this->task->id,
            'equipment' => $this->task->equipment->name,
            'url' => route('vap-maintenance.tasks.show', $this->task),
        ];
    }
}
