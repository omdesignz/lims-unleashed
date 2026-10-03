<?php

namespace App\Actions;

use App\Models\IntegrationConnector;
use App\Models\IntegrationMapping;
use App\Models\InventoryItem;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\IntegrationConnectorValidation;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveIntegrationConnector
{
    public function __construct(
        private readonly IntegrationConnectorValidation $validation,
        private readonly LaboratoryWorkflowOwnership $ownership,
    ) {}

    /** @param array<string,mixed> $data @return array{connector:IntegrationConnector,token:?string} */
    public function execute(int $labId, int $userId, array $data, ?int $connectorId = null): array
    {
        try {
            return DB::transaction(function () use ($labId, $userId, $data, $connectorId): array {
                $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
                abort_unless($lab, 403);
                DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
                User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
                $operator = $this->operator($labId, $userId);
                $connector = new IntegrationConnector;
                $connector->forceFill(['inventory_item_id' => null, 'description' => null, 'health_status' => 'unknown',
                    'health_message' => null, 'last_seen_at' => null, 'last_tested_at' => null, 'deleted_at' => null]);
                if ($connectorId !== null) {
                    $row = IntegrationConnector::query()->where('lab_id', $labId)->whereKey($connectorId)->lockForUpdate()->toBase()->first();
                    abort_unless($row, 404);
                    $connector->setRawAttributes((array) $row, true);
                    $connector->exists = true;
                }
                $data = Validator::make($data, $this->validation->rules($labId, $data, $connector->exists ? $connector : null))->validate();
                $equipment = null;
                $category = null;
                if (array_key_exists('inventory_item_id', $data) && $data['inventory_item_id'] !== null) {
                    $data['inventory_item_id'] = (int) $data['inventory_item_id'];
                    $equipment = InventoryItem::withTrashed()->whereKey($data['inventory_item_id'])->lockForUpdate()->toBase()->first();
                    $category = $equipment ? ItemCategory::withTrashed()->whereKey($equipment->category_id)->lockForUpdate()->toBase()->first() : null;
                    if (! $equipment || (int) $equipment->lab_id !== $labId || $category?->inventory_type !== 'equipment'
                        || ($equipment->deleted_at !== null && $data['inventory_item_id'] !== $connector->inventory_item_id)) {
                        throw ValidationException::withMessages(['inventory_item_id' => 'Seleccione um equipamento elegível deste laboratório.']);
                    }
                }
                $data['key'] = $this->uniqueKey($data['key'] ?? $data['name'], $connectorId);
                $data['configuration'] = array_filter($data['configuration'] ?? [], fn (mixed $value): bool => filled($value));
                $credentials = array_filter($data['credentials'] ?? [], fn (mixed $value): bool => filled($value));
                if ($connector->exists && $credentials === []) {
                    unset($data['credentials']);
                } else {
                    $data['credentials'] = array_merge($connector->credentials ?? [], $credentials);
                }
                $token = null;
                $connector->fill($data);
                if (! $connector->exists) {
                    $token = in_array($connector->direction, ['inbound', 'bidirectional'], true) ? Str::random(64) : null;
                    $connector->forceFill(['uuid' => (string) Str::uuid(), 'lab_id' => $labId, 'created_by_id' => $userId,
                        'signing_secret' => Str::random(64), 'ingest_token_hash' => $token === null ? null : hash('sha256', $token),
                        'event_types' => $data['event_types'] ?? []]);
                    $connector->setCreatedAt($connector->freshTimestamp());
                }
                $needsSave = ! $connector->exists || $connector->isDirty();
                if ($needsSave) {
                    $connector->setUpdatedAt($connector->freshTimestamp());
                }
                $expected = clone $connector;
                if ($needsSave) {
                    abort_unless(IntegrationConnector::withoutTimestamps(fn (): bool => $connector->save()) && $connector->exists && $connector->id,
                        409, 'Não foi possível guardar o conector.');
                }
                $expected->id = $connector->id;
                $this->assertPersisted($expected, ['configuration', 'event_types']);
                $mapping = null;
                if ($connectorId === null && $token !== null) {
                    $mapping = $this->initialMapping($expected, $userId);
                }
                $description = $connectorId === null ? 'Criou um conector no Integration Hub.' : 'Atualizou a configuração de um conector do Integration Hub.';
                $auditExpected = null;
                $audit = activity()->causedBy($operator)->performedOn($expected)->withProperties(['lab_id' => $labId])
                    ->tap(function (ISOActivityLog $entry) use ($description, &$auditExpected): void {
                        $entry->description = $description;
                        $entry->event = null;
                        $entry->setCreatedAt($entry->freshTimestamp());
                        $entry->setUpdatedAt($entry->created_at);
                        $auditExpected = clone $entry;
                    })->log($description);
                abort_unless($audit?->exists && $audit->id && $auditExpected, 409, 'Não foi possível registar a configuração do conector.');
                $auditExpected->id = $audit->id;
                $this->operator($labId, $userId);
                abort_unless((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() === (array) $lab,
                    409, 'O laboratório foi alterado durante a operação.');
                if ($equipment) {
                    abort_unless((array) InventoryItem::withTrashed()->whereKey($equipment->id)->toBase()->first() === (array) $equipment
                        && (array) ItemCategory::withTrashed()->whereKey($category->id)->toBase()->first() === (array) $category,
                        409, 'O equipamento foi alterado durante a operação.');
                }
                $this->assertPersisted($expected, ['configuration', 'event_types']);
                if ($mapping) {
                    $this->assertPersisted($mapping, ['field_paths', 'transformations', 'constants']);
                }
                $this->assertPersisted($auditExpected, ['properties']);
                $expected->exists = true;
                $expected->wasRecentlyCreated = $connectorId === null;
                $expected->syncOriginal();

                return ['connector' => $expected, 'token' => $token];
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'integration_connectors_key_unique')) {
                throw ValidationException::withMessages(['key' => 'Este identificador já está em uso. Tente guardar novamente.']);
            }
            throw $exception;
        }
    }

    private function operator(int $labId, int $userId): User
    {
        abort_unless(VAPLab::query()->whereKey($labId)->toBase()->exists(), 403);
        $operator = $this->ownership->eligibleUsers($labId)->find($userId);
        abort_unless($operator && ($operator->can('edit_iequipments') || $operator->can('edit_settings')), 403);

        return $operator;
    }

    private function initialMapping(IntegrationConnector $connector, int $userId): IntegrationMapping
    {
        $mapping = new IntegrationMapping;
        $mapping->fill(['connector_id' => $connector->id, 'created_by_id' => $userId, 'name' => 'Mapeamento inicial',
            'version' => 1, 'is_active' => true,
            'field_paths' => ['external_id' => 'message.id', 'sample_code' => 'result.sample_code',
                'parameter_code' => 'result.parameter_code', 'value' => 'result.value', 'unit' => 'result.unit', 'measured_at' => 'result.measured_at'],
            'transformations' => ['sample_code' => ['trim', 'uppercase'], 'parameter_code' => ['trim', 'uppercase'],
                'value' => ['trim', 'decimal_comma']], 'constants' => []]);
        $mapping->setCreatedAt($mapping->freshTimestamp());
        $mapping->setUpdatedAt($mapping->created_at);
        $expected = clone $mapping;
        abort_unless(IntegrationMapping::withoutTimestamps(fn (): bool => $mapping->save()) && $mapping->exists && $mapping->id,
            409, 'Não foi possível guardar o mapeamento inicial.');
        $expected->id = $mapping->id;
        $this->assertPersisted($expected, ['field_paths', 'transformations', 'constants']);

        return $expected;
    }

    /** @param list<string> $jsonFields */
    private function assertPersisted(Model $expected, array $jsonFields): void
    {
        $row = $expected->newQueryWithoutScopes()->whereKey($expected->id)->toBase()->first();
        abort_unless($row, 409, 'O registo da integração não foi guardado.');
        $stored = (array) $row;
        abort_unless(count($stored) === count($expected->getAttributes()), 409, 'O registo contém campos fora da operação.');
        foreach ($expected->getAttributes() as $field => $value) {
            $actual = $stored[$field] ?? null;
            if (in_array($field, $jsonFields, true)) {
                $value = $value === null ? null : json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                $actual = $actual === null ? null : json_decode($actual, true, flags: JSON_THROW_ON_ERROR);
            }
            abort_unless(array_key_exists($field, $stored) && $actual === $value, 409, 'O registo guardado não corresponde à operação.');
        }
    }

    private function uniqueKey(string $value, ?int $ignoreId): string
    {
        $base = Str::limit(Str::slug($value) ?: 'connector', 80, '');
        $candidate = $base;
        $suffix = 2;
        while (IntegrationConnector::withTrashed()->where('key', $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = Str::limit($base, 80 - strlen('-'.$suffix), '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
