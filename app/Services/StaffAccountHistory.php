<?php

namespace App\Services;

use App\Models\ISOActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\Activitylog\Models\Activity;

class StaffAccountHistory
{
    public const RETAINED_LOGS = ['staff_account', 'personnel_qualifications', 'laboratory_membership'];

    /**
     * @param  array<string,mixed>  $properties
     * @return array{activity:?Activity,expected:array<string,mixed>}
     */
    public function record(string $logName, User $actor, Model $subject, array $properties, string $event = 'updated'): array
    {
        $description = match ($logName) {
            'staff_account' => match ($event) {
                'updated' => 'actualizou os dados ou acessos de uma conta partilhada',
                'created' => 'criou uma conta partilhada',
                'password_reset' => 'redefiniu a palavra-passe de uma conta partilhada',
                'archived' => 'arquivou uma conta partilhada',
                'restored' => 'restaurou uma conta partilhada',
                'activated' => 'activou uma conta partilhada',
                'deactivated' => 'desactivou uma conta partilhada',
                default => throw new LogicException('Unsupported staff history event.'),
            },
            'personnel_qualifications' => 'actualizou as qualificações de um membro do laboratório',
            'laboratory_membership' => match ($event) {
                'membership_added' => 'adicionou um membro ao laboratório',
                default => throw new LogicException('Unsupported membership history event.'),
            },
            default => throw new LogicException('Unsupported staff history kind.'),
        };
        $expected = ['log_name' => $logName, 'event' => $event, 'description' => $description,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => (int) $subject->getKey(),
            'causer_type' => $actor->getMorphClass(), 'causer_id' => $actor->id, 'properties' => $properties];
        $audit = activity($logName)->causedBy($actor)->performedOn($subject)->event($event)->withProperties($properties)->log($description);

        return ['activity' => $audit, 'expected' => $expected];
    }

    /** @return array<int,array<string,mixed>> */
    public function snapshot(int $targetId): array
    {
        return $this->query($targetId)->lockForUpdate()->get()->mapWithKeys(fn (Activity $audit): array => [$audit->id => $audit->getAttributes()])->all();
    }

    /** @param list<array{activity:?Activity,expected:array<string,mixed>}> $audits
     * @param  array<int,array<string,mixed>>  $before
     */
    public function assertPersisted(array $audits, int $targetId, array $before): void
    {
        foreach ($audits as $audit) {
            $model = $audit['activity'];
            $stored = $model?->exists ? $model->newQueryWithoutScopes()->find($model->id) : null;
            if (! $stored) {
                throw new LogicException('Staff history was not persisted.');
            }
            $actual = $stored->only(array_keys($audit['expected']));
            $actual['subject_id'] = (int) $stored->subject_id;
            $actual['causer_id'] = (int) $stored->causer_id;
            $actual['properties'] = $stored->properties->all();
            if ($this->canonical($actual) !== $this->canonical($audit['expected'])) {
                throw new LogicException('Persisted staff history differs from the intended history.');
            }
        }
        $history = $this->snapshot($targetId);
        foreach ($audits as $audit) {
            unset($history[$audit['activity']->id]);
        }
        if ($history !== $before) {
            throw new LogicException('Retained staff history differs from the intended history.');
        }
    }

    public function isRetained(Activity $activity): bool
    {
        return in_array($activity->log_name, self::RETAINED_LOGS, true);
    }

    private function query(int $targetId): Builder
    {
        return ISOActivityLog::withoutGlobalScopes()->whereIn('log_name', self::RETAINED_LOGS)
            ->where('properties->target_user_id', $targetId)->orderBy('id');
    }

    /** @param array<mixed> $values
     * @return array<mixed>
     */
    private function canonical(array $values): array
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $value = $this->canonical($value);
            }
        }
        unset($value);
        if (! array_is_list($values)) {
            ksort($values);
        }

        return $values;
    }
}
