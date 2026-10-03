<?php

namespace App\Services;

use App\Models\DocumentDelivery;
use App\Models\QualityCertificate;
use App\Models\User;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class SharedDocumentDeliveryAccess
{
    public function __construct(
        private readonly ShareableDocumentRegistry $documents,
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorkflowOwnership $ownership,
    ) {}

    /** @return array{document: Model, operator: User, fingerprint: string} */
    public function authorize(int $userId, int $labId, string $type, int $documentId, bool $lock = false): array
    {
        $definition = $this->documents->definition($type) ?? throw new InvalidArgumentException('Unsupported document delivery source.');
        $operator = $this->access->operator($userId, $labId, $definition['permission']);
        $document = $definition['model']::query()->withoutGlobalScope('financial_laboratory')
            ->with($definition['relations'])->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($documentId);
        $this->assertOwner($document, $labId);
        if ($document instanceof QualityCertificate) {
            $document->loadMissing(['collection.product.matrix', 'collection.packaging', 'collection.sampleEntry.customerRequest',
                'collection.code', 'results.parameter', 'results.unit', 'results.profile', 'results.standard',
                'results.protocol', 'results.counter_analysis', 'results.sample']);
            foreach ($document->results as $result) {
                $entry = $this->ownership->resolve(['result_id' => $result->id, 'lab_id' => $labId,
                    'collection_product_id' => $document->collection_id]);
                if (! $entry || ! $result->sample || (int) $result->sample->cl_id !== (int) $document->cl_id
                    || ($result->counter_analysis && ! $this->ownership->resolve([
                        'counter_analysis_id' => $result->counter_analysis->id, 'sample_entry_id' => $entry->id,
                    ]))) {
                    throw new AuthorizationException('O boletim contém resultados sem uma origem laboratorial coerente.');
                }
            }
        }

        return ['document' => $document, 'operator' => $operator,
            'fingerprint' => hash('sha256', serialize($this->graph($document)))];
    }

    public function owner(Model $document): int
    {
        if ($document instanceof QualityCertificate) {
            $entry = $this->ownership->resolve(['lab_code_id' => $document->cl_id]);
            if (! $entry || (int) $entry->collection_product_id !== (int) $document->collection_id
                || (int) $entry->customer_id !== (int) $document->customer_id
                || (int) $entry->warehouse_id !== (int) $document->warehouse_id) {
                throw new AuthorizationException('O boletim não possui uma origem laboratorial válida.');
            }

            return (int) $entry->lab_id;
        }

        return (int) $document->lab_id;
    }

    public function assertOwner(Model $document, int $labId): void
    {
        if ($labId <= 0 || $this->owner($document) !== $labId) {
            throw new AuthorizationException('O documento não pertence ao laboratório do envio.');
        }
    }

    /** @return array<string, mixed> */
    private function graph(Model $model): array
    {
        $relations = [];
        foreach ($model->getRelations() as $name => $value) {
            $relations[$name] = match (true) {
                $value instanceof Model => $this->graph($value),
                $value instanceof Collection => $value->map(fn (Model $related): array => $this->graph($related))->all(),
                default => null,
            };
        }

        return ['class' => $model::class, 'attributes' => $model->getAttributes(), 'relations' => $relations];
    }

    /** @return array<string, mixed> */
    public function deliveryIdentity(DocumentDelivery $delivery): array
    {
        return $delivery->only(['lab_id', 'sender_id', 'document_type', 'document_id', 'recipients', 'cc', 'subject', 'message']);
    }

    /** @param array<string, mixed> $context */
    public function canReceive(User $user, array $context): bool
    {
        $delivery = DocumentDelivery::query()->whereKey($context['document_delivery_id'] ?? null)
            ->where('lab_id', $context['lab_id'] ?? null)->where('sender_id', $user->id)->first();
        if (! $delivery) {
            return false;
        }
        try {
            $this->authorize($user->id, (int) $delivery->lab_id, $delivery->document_type, (int) $delivery->document_id);

            return true;
        } catch (AuthorizationException|ModelNotFoundException) {
            return false;
        }
    }
}
