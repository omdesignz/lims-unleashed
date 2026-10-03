<?php

namespace App\Models;

use Database\Factories\LabNetworkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabNetwork extends Model
{
    /** @use HasFactory<LabNetworkFactory> */
    use HasFactory;

    protected $fillable = ['name', 'main_lab_id', 'primary_color'];

    public function labs(): HasMany
    {
        return $this->hasMany(VAPLab::class, 'network_id');
    }
}
