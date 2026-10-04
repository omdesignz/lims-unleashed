<?php

namespace App\Actions;

use App\Http\Requests\IssuePortalServiceInvitationRequest;
use App\Models\Customer;
use App\Models\PortalServiceInvitation;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssuePortalServiceInvitation
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $userId, array $data): PortalServiceInvitation
    {
        abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);

        return DB::transaction(function () use ($labId, $userId, $data): PortalServiceInvitation {
            $operator = $this->access->operator($userId, $labId, 'add_customer_requests');
            $validated = Validator::make($data, (new IssuePortalServiceInvitationRequest)->rules())->validate();
            $recipient = Warehouse::query()->where('email', $validated['recipient_email'])
                ->whereNotNull('email_verified_at')->lockForUpdate()->first();
            if (! $recipient || ! Customer::query()->lockForUpdate()->find($recipient->customer_id)) {
                throw ValidationException::withMessages(['recipient_email' => 'Seleccione uma conta do portal verificada e com cliente activo.']);
            }
            $existing = PortalServiceInvitation::query()->availableTo($recipient)->where('lab_id', $labId)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }
            $invitation = new PortalServiceInvitation([
                'lab_id' => $labId, 'warehouse_id' => $recipient->id, 'customer_id' => $recipient->customer_id,
                'issued_by_id' => $operator->id, 'token' => (string) Str::uuid(), 'expires_at' => now()->addDays(30),
            ]);
            abort_unless($invitation->save(), 409);

            return $invitation;
        }, 3);
    }
}
