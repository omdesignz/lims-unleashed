<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\PortalServiceInvitation;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowOwnership;
use App\Support\NotificationTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitPortalServiceRequest
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly NotificationTemplateService $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $warehouseId, array $data): CustomerRequest
    {
        return DB::transaction(function () use ($warehouseId, $data): CustomerRequest {
            $recipient = Warehouse::query()->whereNotNull('email_verified_at')->lockForUpdate()->findOrFail($warehouseId);
            abort_unless(Customer::query()->lockForUpdate()->find($recipient->customer_id), 403);
            $invitation = PortalServiceInvitation::query()->availableTo($recipient)
                ->where('token', $data['invitation'])->lockForUpdate()->first();
            if (! $invitation || ! VAPLab::query()->lockForUpdate()->find($invitation->lab_id)) {
                throw ValidationException::withMessages(['invitation' => 'Seleccione um convite válido emitido pelo laboratório para esta conta.']);
            }
            $record = new CustomerRequest([
                'lab_id' => $invitation->lab_id, 'warehouse_id' => $recipient->id, 'customer_id' => $recipient->customer_id,
                'category_id' => $data['category_id'] ?? null, 'title' => $data['title'], 'request_type' => $data['request_type'],
                'status' => 'pending', 'priority' => $data['priority'] ?? 'normal', 'preferred_date' => $data['preferred_date'] ?? null,
                'submitted_at' => now(), 'description' => $data['description'], 'contact' => $data['contact'], 'email' => $data['email'],
                'answered' => false, 'extra_data' => $data['details'] ?? [],
            ]);
            abort_unless($record->save(), 409);
            abort_unless($record->update(['reference' => 'REQ-'.now()->format('Y').'-'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT)]), 409);
            abort_unless($invitation->update(['customer_request_id' => $record->id, 'consumed_at' => now()]), 409);
            DB::afterCommit(function () use ($record, $recipient): void {
                $recipients = $this->ownership->eligibleUsers((int) $record->lab_id)->get()
                    ->filter(fn ($user): bool => $user->can('view_customer_requests'));
                $this->notifications->notify($recipients, 'commercial.portal_request.created', [
                    'lab_id' => $record->lab_id, 'customer_name' => $recipient->name,
                    'document_number' => $record->reference, 'request_type' => $record->request_type,
                    'document_url' => route('customerrequests.index'),
                ]);
            });

            return $record;
        }, 3);
    }
}
