<?php

namespace App\Notifications;

use App\Models\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    use Queueable;

    public $inventory;

    public $sender;

    public function __construct(Inventory $inventory, $sender)
    {
        $this->inventory = $inventory;
        $this->sender = $sender;
    }

    public function via($notifiable)
    {
        return [
            'database',
        ]; // Change to ['mail', 'database'] if needed
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Alerta de existências baixas: {$this->inventory->item->name}")
            ->line("O artigo **{$this->inventory->item->name}** tem poucas existências disponíveis.")
            ->line("Quantidade disponível: {$this->inventory->qty_available}")
            ->line("Quantidade mínima: {$this->inventory->min_stock_level}")
            ->line('Verifique as existências e proceda à reposição.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Alerta de existências baixas',
            'message' => "O artigo **{$this->inventory->item->name}** tem poucas existências disponíveis.\nQuantidade disponível: {$this->inventory->qty_available}\nQuantidade mínima: {$this->inventory->min_stock_level}\nVerifique as existências e proceda à reposição.",
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
        ];
    }
}
