<?php

namespace App\Actions;

use App\Models\InventoryNeed;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectInventoryNeed
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    public function execute(int $userId, int $labId, int $needId, string $notes): InventoryNeed
    {
        return DB::transaction(function () use ($userId, $labId, $needId, $notes): InventoryNeed {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $this->access->operator($userId, $labId, 'edit_iorders');
            $need = InventoryNeed::forLaboratory($labId)->lockForUpdate()->findOrFail($needId);
            if ($need->status !== 'submitted' || $need->inventory_order_id !== null) {
                throw ValidationException::withMessages(['need' => 'Esta necessidade já não aguarda uma decisão. Actualize a página.']);
            }
            abort_unless($need->update([
                'status' => 'rejected', 'approval_notes' => $notes,
                'approved_by_id' => $userId, 'rejected_at' => now(),
            ]), 409, 'Não foi possível guardar a rejeição.');
            $this->access->operator($userId, $labId, 'edit_iorders');

            return $need;
        });
    }
}
