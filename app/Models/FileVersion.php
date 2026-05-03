<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasFactory;

    protected $fillable = ['file_id', 'path', 'name', 'extension', 'size'];

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
