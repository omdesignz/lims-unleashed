<?php

namespace App\Actions;

use App\Models\CustomerRequest;
use App\Models\Proposal;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\PersonnelQualificationGate;
use App\Support\SampleEntryCollectionFlowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSampleEntry
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly PersonnelQualificationGate $qualifications,
        private readonly PrepareSampleEntryPayload $preparePayload,
        private readonly SampleEntryCollectionFlowService $collectionFlow,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{sample: VAPSampleEntry, previous_status: string}
     */
    public function execute(int $labId, int $sampleEntryId, int $userId, array $data): array
    {
        return DB::transaction(function () use ($labId, $sampleEntryId, $userId, $data): array {
            $operator = $this->access->operator($userId, $labId, 'edit_samples');
            $entry = VAPSampleEntry::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($sampleEntryId);
            $this->ensureIdentityIsUnchanged($entry, $data);
            $this->qualifications->ensure($operator, 'sample_intake_validation', (int) ($data['department_id'] ?? $entry->department_id), $labId);

            $customerId = $data['customer_id'] ?? $entry->customer_id;
            $warehouseId = $data['warehouse_id'] ?? $entry->warehouse_id;

            if (! Warehouse::query()->whereKey($warehouseId)->where('customer_id', $customerId)->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['warehouse_id' => 'O local deve pertencer ao cliente da amostra.']);
            }

            $portalRequest = $entry->customer_request_id
                ? CustomerRequest::query()->where('customer_id', $customerId)->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()->findOrFail($entry->customer_request_id)
                : null;
            $proposalId = array_key_exists('proposal_id', $data) ? $data['proposal_id'] : $entry->proposal_id;
            $proposal = $proposalId
                ? Proposal::query()->where('lab_id', $labId)->lockForUpdate()->find($proposalId)
                : null;

            if ($proposalId && ! $proposal) {
                throw ValidationException::withMessages(['proposal_id' => 'Seleccione uma proposta deste laboratório.']);
            }

            $prepared = $this->preparePayload->execute($data, $portalRequest, $entry, $proposal);
            $this->ensureIdentityIsUnchanged($entry, $prepared);
            $previousStatus = $entry->status;
            $entry->update($prepared);
            $record = $this->collectionFlow->sync($entry);

            if ($record) {
                $this->collectionFlow->correctMetadata($entry, $record, $data);
            }

            return ['sample' => $entry->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']), 'previous_status' => $previousStatus];
        });
    }

    /** @param array<string, mixed> $data */
    private function ensureIdentityIsUnchanged(VAPSampleEntry $entry, array $data): void
    {
        $fields = ['code', 'lab_id', 'collection_product_id', 'customer_request_id'];

        if ($entry->collection_product_id) {
            $fields = array_merge($fields, ['customer_id', 'warehouse_id', 'department_id', 'sample_type', 'requested_services']);
        }

        $errors = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && ! $this->sameValue($data[$field], $entry->{$field})) {
                $errors[$field] = 'A identidade emitida da amostra não pode ser alterada. Corrija apenas os metadados.';
            }
        }

        if (array_key_exists('portal_request_id', $data) && ! $this->sameValue($data['portal_request_id'], $entry->customer_request_id)) {
            $errors['portal_request_id'] = 'A origem da amostra emitida não pode ser substituída.';
        }

        $payload = $data['client_submitted_info'] ?? [];
        $protectedFields = array_merge(PrepareSampleEntryPayload::SYSTEM_FIELDS, PrepareSampleEntryPayload::EVIDENCE_FIELDS, PrepareSampleEntryPayload::BATCH_FIELDS);

        if ($entry->collection_product_id) {
            $protectedFields = array_merge($protectedFields, [
                'product_id', 'matrix_id', 'requested_profile_ids', 'collection_type', 'request_origin', 'analysis_discipline', 'batch_sample_index',
            ]);
        }

        foreach ($protectedFields as $field) {
            $original = data_get($entry->client_submitted_info, $field);

            if ($field === 'request_origin' && $original === null) {
                $original = 'client';
            }

            if (array_key_exists($field, $payload) && ! $this->sameValue($payload[$field], $original, $field === 'requested_profile_ids')) {
                $errors['client_submitted_info.'.$field] = 'O âmbito e as ligações emitidas da amostra não podem ser alterados.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function sameValue(mixed $submitted, mixed $original, bool $unordered = false): bool
    {
        if ($unordered) {
            return collect($submitted ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all()
                === collect($original ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
        }

        if (is_array($submitted) || is_array($original)) {
            return $submitted === $original;
        }

        return (string) $submitted === (string) $original;
    }
}
