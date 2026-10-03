<?php

namespace App\Actions;

use App\Jobs\SendSharedDocumentEmail;
use App\Models\DocumentDelivery;
use App\Services\SharedDocumentDeliveryAccess;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;

class QueueSharedDocumentDelivery
{
    public function __construct(private readonly SharedDocumentDeliveryAccess $access) {}

    /** @param array{document_type: string, document_id: int, recipients: list<string>, cc?: list<string>|null, subject: string, message: string} $data */
    public function execute(int $userId, int $labId, array $data): DocumentDelivery
    {
        return DB::transaction(function () use ($userId, $labId, $data): DocumentDelivery {
            $source = $this->access->authorize($userId, $labId, $data['document_type'], (int) $data['document_id'], true);
            $delivery = new DocumentDelivery($data);
            $delivery->forceFill(['lab_id' => $labId, 'sender_id' => $userId, 'status' => 'queued']);
            $delivery->document_id = (int) $data['document_id'];
            $delivery->cc ??= null;
            $identity = $this->access->deliveryIdentity($delivery);
            if (! $delivery->save()) {
                throw new LogicException('Document delivery was not persisted.');
            }
            $audit = activity()->causedBy($source['operator'])->performedOn($source['document'])
                ->event('delivery_queued')->withProperties([
                    'lab_id' => $labId, 'document_type' => $delivery->document_type, 'document_id' => $delivery->document_id,
                    'delivery_id' => $delivery->id, 'recipient_count' => count($delivery->recipients),
                ])->log('Agendou o envio de um documento por correio electrónico.');
            $storedAudit = $audit?->newQueryWithoutScopes()->find($audit->id);
            $freshSource = $this->access->authorize($userId, $labId, $data['document_type'], (int) $data['document_id'], true);
            $persisted = $delivery->fresh();
            if (! $persisted || $persisted->status !== 'queued' || $this->access->deliveryIdentity($persisted) !== $identity
                || $freshSource['fingerprint'] !== $source['fingerprint']
                || ! $storedAudit || $storedAudit->subject_type !== $source['document']->getMorphClass()
                || (int) $storedAudit->subject_id !== (int) $source['document']->id
                || $storedAudit->causer_type !== $source['operator']->getMorphClass()
                || (int) $storedAudit->causer_id !== $userId || $storedAudit->event !== 'delivery_queued'
                || (int) $storedAudit->properties->get('lab_id') !== $labId
                || (int) $storedAudit->properties->get('delivery_id') !== (int) $delivery->id
                || $storedAudit->properties->get('document_type') !== $identity['document_type']
                || (int) $storedAudit->properties->get('document_id') !== $identity['document_id']
                || (int) $storedAudit->properties->get('recipient_count') !== count($identity['recipients'])) {
                throw new LogicException('Document delivery source, identity or audit changed during queue preparation.');
            }
            DB::afterCommit(function () use ($persisted): void {
                $job = new SendSharedDocumentEmail($persisted);
                try {
                    Bus::dispatch($job);
                } catch (Throwable $exception) {
                    try {
                        $job->failed($exception);
                    } catch (Throwable $persistenceFailure) {
                        report($persistenceFailure);
                    }
                    throw $exception;
                }
            });

            return $persisted;
        }, 3);
    }
}
