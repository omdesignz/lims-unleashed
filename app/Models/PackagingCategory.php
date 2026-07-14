<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PackagingCategory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const MENU_NAME = 'packaging_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $events = ['*'];

    protected $table = 'packaging_categories';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => 'Categoria de embalagem '.match ($eventName) {
                'created' => 'criada',
                'updated' => 'actualizada',
                'deleted' => 'eliminada',
                'restored' => 'restaurada',
                default => $eventName,
            });
        // Chain fluent methods for configuration options
    }
}
