<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchivedDocument extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'archived_documents';

    protected $fillable = ['title', 'file_path', 'description'];
}
