<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProficiencyTest extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'proficiency_tests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'date',
        'scheme_type',
        'provider_name',
        'round_reference',
        'status',
        'scheduled_at',
        'closed_at',
        'scope',
        'outcome',
        'z_score',
        'corrective_actions',
        'notes',
        'results',
    ];

    protected $table = 'proficiency_tests';

    protected $casts = [
        'date' => 'date',
        'scheduled_at' => 'datetime',
        'closed_at' => 'datetime',
        'results' => 'array',
        'z_score' => 'decimal:2',
    ];
}
