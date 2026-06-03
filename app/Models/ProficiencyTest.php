<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'role',
        'provider_name',
        'organizer_name',
        'participants',
        'parameters',
        'round_reference',
        'status',
        'scheduled_at',
        'enrollment_deadline_at',
        'submission_deadline_at',
        'closed_at',
        'scope',
        'outcome',
        'z_score',
        'corrective_actions',
        'notes',
        'results',
        'participant_results',
        'assigned_values',
        'performance_summary',
    ];

    protected $table = 'proficiency_tests';

    protected $casts = [
        'date' => 'date',
        'scheduled_at' => 'datetime',
        'enrollment_deadline_at' => 'datetime',
        'submission_deadline_at' => 'datetime',
        'closed_at' => 'datetime',
        'participants' => 'array',
        'parameters' => 'array',
        'results' => 'array',
        'participant_results' => 'array',
        'assigned_values' => 'array',
        'performance_summary' => 'array',
        'z_score' => 'decimal:2',
    ];

    public function deadlineDate(): ?Carbon
    {
        return $this->submission_deadline_at ?? $this->scheduled_at ?? $this->date;
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    public function participantCount(): int
    {
        return count($this->participants ?? []);
    }

    public function parameterCount(): int
    {
        return count($this->parameters ?? []);
    }

    public function resultCount(): int
    {
        return collect($this->participant_results ?? [])
            ->sum(fn (array $participant) => count($participant['results'] ?? []));
    }

    /**
     * @return array<string, int>
     */
    public function participantStatusCounts(): array
    {
        return collect($this->participants ?? [])
            ->map(fn (array $participant) => $participant['status'] ?? 'pending')
            ->countBy()
            ->union([
                'pending' => 0,
                'enrolled' => 0,
                'submitted' => 0,
                'reviewed' => 0,
                'requires_action' => 0,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function calculatePerformanceSummary(): array
    {
        $participantResults = collect($this->participant_results ?? []);
        $allResults = $participantResults
            ->flatMap(fn (array $participant) => collect($participant['results'] ?? [])->map(function (array $result) use ($participant) {
                return [
                    ...$result,
                    'participant_code' => $participant['code'] ?? null,
                    'participant_name' => $participant['name'] ?? null,
                ];
            }))
            ->values();

        $zScores = $allResults
            ->pluck('z_score')
            ->filter(fn ($score) => is_numeric($score))
            ->map(fn ($score) => (float) $score)
            ->values();

        $unsatisfactory = $zScores->filter(fn (float $score) => abs($score) >= 3)->count();
        $questionable = $zScores->filter(fn (float $score) => abs($score) >= 2 && abs($score) < 3)->count();

        return [
            'participants' => $this->participantCount(),
            'parameters' => $this->parameterCount(),
            'results' => $allResults->count(),
            'average_z_score' => $zScores->isEmpty() ? null : round($zScores->avg(), 2),
            'max_abs_z_score' => $zScores->isEmpty() ? null : round($zScores->map(fn (float $score) => abs($score))->max(), 2),
            'participant_statuses' => $this->participantStatusCounts(),
            'satisfactory' => max($zScores->count() - $questionable - $unsatisfactory, 0),
            'questionable' => $questionable,
            'unsatisfactory' => $unsatisfactory,
        ];
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function deadlineState(): string
    {
        if ($this->isClosed()) {
            return 'closed';
        }

        $deadline = $this->deadlineDate();

        if (! $deadline) {
            return 'unscheduled';
        }

        if ($deadline->isPast()) {
            return 'overdue';
        }

        if ($deadline->isBefore(now()->addDays(14))) {
            return 'due_soon';
        }

        return 'on_track';
    }
}
