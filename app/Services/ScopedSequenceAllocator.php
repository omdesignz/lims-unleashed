<?php

namespace App\Services;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use OverflowException;

class ScopedSequenceAllocator
{
    /**
     * @param  array{group?: string|array<int, string>, fieldName?: string}  $configuration
     */
    public function assign(Model $model, array $configuration): void
    {
        $field = $configuration['fieldName'] ?? 'seq';
        $groups = $this->groups($configuration);

        if (in_array($field, $groups, true)) {
            throw new LogicException('A sequence field cannot be part of its own scope.');
        }

        $scope = [];

        foreach ($groups as $group) {
            $value = $model->getAttribute($group);

            $scope[$group] = match (true) {
                $value instanceof BackedEnum => (string) $value->value,
                $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
                $value === null => null,
                is_scalar($value) => (string) $value,
                default => throw new InvalidArgumentException('Sequence scopes must contain scalar values.'),
            };
        }

        ksort($scope);

        $requested = $model->getAttribute($field);

        if ($requested !== null && filter_var($requested, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            throw new InvalidArgumentException('Sequence numbers must be non-negative integers.');
        }

        $requested = (int) $requested;
        $connection = $model->getConnection();
        $scopeJson = json_encode($scope, JSON_THROW_ON_ERROR);
        $hash = hash('sha256', json_encode([$model->getTable(), $field, $scope], JSON_THROW_ON_ERROR));

        $number = $connection->transaction(function () use ($connection, $model, $field, $scope, $scopeJson, $hash, $requested): int {
            $connection->table('sequence_counters')->insertOrIgnore([
                'scope_hash' => $hash,
                'table_name' => $model->getTable(),
                'field_name' => $field,
                'scope_values' => $scopeJson,
                'last_value' => 0,
            ]);

            $counter = $connection->table('sequence_counters')->where('scope_hash', $hash)->lockForUpdate()->first();
            $existing = $model->newQueryWithoutScopes();

            foreach ($scope as $group => $value) {
                $existing->where($group, $value);
            }

            $numericType = $connection->getDriverName() === 'mysql' ? 'SIGNED' : 'BIGINT';
            $numericField = $connection->raw('CAST('.$connection->getQueryGrammar()->wrap($field).' AS '.$numericType.')');
            $highest = max((int) $counter->last_value, (int) (clone $existing)->max($numericField));

            if ($requested > 0 && $requested <= $highest) {
                throw new LogicException('Explicit sequence numbers must be higher than every number already issued or reserved in their scope.');
            }

            if ($requested === 0 && $highest === PHP_INT_MAX) {
                throw new OverflowException('The sequence has reached its maximum value.');
            }

            $number = $requested > 0 ? $requested : $highest + 1;

            $connection->table('sequence_counters')->where('scope_hash', $hash)->update([
                'last_value' => max($highest, $number),
            ]);

            return $number;
        }, attempts: 5);

        $model->setAttribute($field, $number);
    }

    /**
     * @param  array{group?: string|array<int, string>, fieldName?: string}  $configuration
     * @return array<int, string>
     */
    public function groups(array $configuration): array
    {
        $groups = $configuration['group'] ?? [];

        return array_values(array_filter((array) $groups, fn (string $group): bool => $group !== ''));
    }
}
