<?php

namespace App\Listeners;

use App\Models\ApplicationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailOnEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        //
        $eventModel = ApplicationEvent::where('name', get_class($event))->first();

        if ($eventModel && $eventModel->emailTemplate) {
            $template = $eventModel->emailTemplate;

            $subject = $template->subject;
            $body = str_replace(
                ['{{username}}'], // Replace these with actual placeholders
                [$event->user->name ?? ''],
                $template->body
            );

            Mail::raw($body, function ($message) use ($event, $subject) {
                $message->to($event->user->email ?? 'default@example.com')
                        ->subject($subject);
            });
        }
    }
    
}
