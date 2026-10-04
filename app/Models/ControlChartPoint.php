<?php

namespace App\Models;

use Database\Factories\ControlChartPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlChartPoint extends Model
{
    /** @use HasFactory<ControlChartPointFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'control_chart_id',
        'measured_at',
        'value',
        'replicate_a',
        'replicate_b',
        'run_reference',
        'notes',
        'excluded',
        'exclusion_reason',
        'corrective_action',
        'corrective_action_at',
        'corrective_action_by_id',
        'recorded_by_id',
        'result_id',
        'paired_result_id',
        'sample_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
            'value' => 'float',
            'replicate_a' => 'float',
            'replicate_b' => 'float',
            'excluded' => 'boolean',
            'corrective_action_at' => 'datetime',
        ];
    }

    public function chart(): BelongsTo
    {
        return $this->belongsTo(ControlChart::class, 'control_chart_id');
    }

    /** The quality control sample whose approved result this point is. */
    public function sampleEntry(): BelongsTo
    {
        return $this->belongsTo(VAPSampleEntry::class, 'sample_entry_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function correctiveActionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrective_action_by_id');
    }
}
