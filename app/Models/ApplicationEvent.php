<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    const MENU_NAME = 'app_events';
    
    protected $fillable = ['name', 'description'];

    public function emailTemplate()
    {
        return $this->hasOneThrough(ApplicationEventEmailTemplate::class, 'event_email_template');
    }
}
