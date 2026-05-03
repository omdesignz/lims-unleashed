<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;
    public const MENU_NAME = 'attachments';

    protected $fillable = ['message_id', 'file_path', 'file_type'];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
