<?php

namespace App\Models;

use App\Support\ControlChartEvaluation;
use Database\Factories\ControlChartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A control chart of one test in one laboratory (ISO/IEC 17025:2017, 7.7.1).
 * Changes to its limits are kept in the activity log.
 */
class ControlChart extends Model
{
    /** @use HasFactory<ControlChartFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public const MENU_NAME = 'control_charts';

    public const STATUSES = ['active' => 'Activa', 'archived' => 'Arquivada'];

    public const TYPES = [
        ControlChartEvaluation::MEAN => 'Carta de médias (X)',
        ControlChartEvaluation::RANGE => 'Carta de amplitudes (R)',
    ];

    public const LIMIT_SOURCES = ['entered' => 'Introduzidos', 'computed' => 'Calculados a partir dos pontos'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lab_id',
        'name',
        'chart_type',
        'parameter_id',
        'method',
        'matrix',
        'control_material',
        'material_lot',
        'unit',
        'centre_line',
        'standard_deviation',
        'limits_source',
        'limits_basis',
        'limits_point_count',
        'limits_set_at',
        'limits_set_by_id',
        'status',
        'notes',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'centre_line' => 'float',
            'standard_deviation' => 'float',
            'limits_point_count' => 'integer',
            'limits_set_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'chart_type', 'parameter_id', 'method', 'control_material', 'material_lot', 'unit',
                'centre_line', 'standard_deviation', 'limits_source', 'limits_basis', 'limits_point_count', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('control_charts');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class)->withTrashed();
    }

    public function limitsSetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'limits_set_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(ControlChartPoint::class)->orderBy('measured_at')->orderBy('id');
    }

    /**
     * @return array{centre: float, upper_warning: float, upper_action: float, lower_warning: ?float, lower_action: ?float}|null
     */
    public function limits(): ?array
    {
        return ControlChartEvaluation::limits($this->chart_type, $this->centre_line, $this->standard_deviation);
    }

    public function isRange(): bool
    {
        return $this->chart_type === ControlChartEvaluation::RANGE;
    }
}
