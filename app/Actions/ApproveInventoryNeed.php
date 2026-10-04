<?php

namespace App\Actions;

use App\Models\InventoryNeed;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveInventoryNeed
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array{approval_notes?: ?string, items: list<array{id: int, quantity_approved: string|int|float}>} $data */
    public function execute(int $userId, int $labId, int $needId, array $data): InventoryNeed
    {
        return DB::transaction(function () use ($userId, $labId, $needId, $data): InventoryNeed {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $this->access->operator($userId, $labId, 'edit_iorders');
            $need = InventoryNeed::forLaboratory($labId)->lockForUpdate()->findOrFail($needId);
            if ($need->status !== 'submitted' || $need->inventory_order_id !== null) {
                throw ValidationException::withMessages(['need' => 'Esta necessidade já não aguarda uma decisão. Actualize a página.']);
            }

            $items = $need->items()->orderBy('id')->lockForUpdate()->get();
            $decisions = collect($data['items'])->keyBy('id');
            if ($items->isEmpty() || $items->count() !== count($data['items'])
                || $items->modelKeys() !== $decisions->keys()->map(fn ($id): int => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => 'Reveja a quantidade aprovada de todos os itens da necessidade.']);
            }

            foreach ($items as $item) {
                $quantity = $decisions[$item->id]['quantity_approved'];
                if ((int) $item->lab_id !== $labId || InventoryQuantity::compare($quantity, 0) <= 0
                    || InventoryQuantity::compare($quantity, $item->quantity_requested) > 0) {
                    throw ValidationException::withMessages(['items' => 'A quantidade aprovada deve ser positiva e não pode exceder a quantidade solicitada.']);
                }
                abort_unless($item->update([
                    'quantity_approved' => InventoryQuantity::fromScaled(InventoryQuantity::toScaled($quantity)),
                    'status' => 'approved',
                ]), 409, 'Não foi possível guardar a aprovação dos itens.');
            }
            abort_unless($need->update([
                'status' => 'approved', 'approval_notes' => $data['approval_notes'] ?? null,
                'approved_by_id' => $userId, 'approved_at' => now(), 'rejected_at' => null,
            ]), 409, 'Não foi possível guardar a aprovação.');
            $this->access->operator($userId, $labId, 'edit_iorders');

            return $need;
        });
    }
}
