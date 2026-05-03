<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Inventory;

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
            'database'
        ]; // Change to ['mail', 'database'] if needed
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("⚠️ Alerta de Estoque Baixo: {$this->inventory->item->name}")
            ->line("O item **{$this->inventory->item->name}** está com pouca quantidade disponível.")
            ->line("Quantidade Disponível: {$this->inventory->qty_available}")
            ->line("Quantidade Mínima de Estoque: {$this->inventory->min_stock_level}")
            ->line("Por favor, verifique o estoque e reajuste.");
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '⚠️ Alerta de Estoque Baixo',
            'message' => "O item **{$this->inventory->item->name}** está com pouca quantidade disponível. ().\nQuantidade Disponível: {$this->inventory->qty_available}\nQuantidade Mínima de Estoque: {$this->inventory->min_stock_level}\nPor favor, verifique o estoque e reajuste.",
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
        ];
    }
}