<?php

namespace App\Actions;

use App\Enums\Orders\InventoryOrderItemStatus;
use App\Enums\Orders\InventoryOrderTrackingStatus;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConvertInventoryNeedToOrder
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array{supplier_id: int, date: string, expected_date?: ?string, reference?: ?string, obs?: ?string} $data */
    public function execute(int $userId, int $labId, int $needId, array $data): InventoryOrder
    {
        return DB::transaction(function () use ($userId, $labId, $needId, $data): InventoryOrder {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $this->access->operator($userId, $labId, 'add_iorders');
            $need = InventoryNeed::forLaboratory($labId)->lockForUpdate()->findOrFail($needId);
            if ($need->status !== 'approved' || $need->inventory_order_id !== null) {
                throw ValidationException::withMessages(['need' => 'A necessidade não está aprovada ou já foi convertida num pedido. Actualize a página.']);
            }
            $supplier = InventoryItemSupplier::query()->lockForUpdate()->findOrFail($data['supplier_id']);
            $assessment = InventorySupplierAssessment::query()->where('lab_id', $labId)
                ->where('inventory_item_supplier_id', $supplier->id)->latest('assessment_date')->latest('id')
                ->lockForUpdate()->first();
            if ($assessment && (in_array($assessment->status, ['rejected', 'suspended'], true)
                || ($assessment->risk_level === 'critical' && ! $assessment->approved_supplier))) {
                throw ValidationException::withMessages(['supplier_id' => 'Este fornecedor está bloqueado pela avaliação mais recente e não pode ser usado nesta aquisição.']);
            }
            $items = $need->items()->orderBy('id')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'A necessidade não tem itens aprovados.']);
            }
            foreach ($items as $item) {
                if ((int) $item->lab_id !== $labId || $item->status !== 'approved' || $item->quantity_approved === null
                    || InventoryQuantity::compare($item->quantity_approved, 0) <= 0
                    || InventoryQuantity::compare($item->quantity_approved, $item->quantity_requested) > 0) {
                    throw ValidationException::withMessages(['items' => 'Todos os itens devem ter uma quantidade aprovada válida antes da compra.']);
                }
                $material = InventoryItem::query()->where('lab_id', $labId)->whereNotNull('unit_id')
                    ->lockForUpdate()->find($item->inventory_item_id);
                $warehouse = $item->warehouse_id === null ? null : InventoryItemWarehouse::query()
                    ->where('lab_id', $labId)->lockForUpdate()->find($item->warehouse_id);
                if (! $material || ($item->warehouse_id !== null && ! $warehouse)) {
                    throw ValidationException::withMessages(['items' => 'Um material ou armazém já não está disponível neste laboratório.']);
                }
            }

            $order = new InventoryOrder([
                'lab_id' => $labId, 'date' => $data['date'], 'user_id' => $userId,
                'supplier_id' => $supplier->id, 'order_year' => now()->format('Y'),
                'reference' => $data['reference'] ?? null,
                'obs' => trim(($data['obs'] ?? '')."\nOrigem: necessidade {$need->reference}"),
                'status' => InventoryOrderTrackingStatus::PENDING,
                'currency' => $supplier->currency ?? 'USD', 'total_amount' => 0,
            ]);
            abort_unless($order->save(), 409, 'Não foi possível criar o pedido.');
            $totalAmount = 0;
            foreach ($items as $item) {
                $quantity = $item->quantity_approved;
                $unitPrice = $item->estimated_unit_price ?? '0.00';
                $detail = new InventoryOrderDetail([
                    'order_id' => $order->id, 'item_id' => $item->inventory_item_id,
                    'qty' => $quantity, 'received_qty' => 0, 'unit_price' => $unitPrice,
                    'warehouse_id' => $item->warehouse_id,
                    'expected_date' => $data['expected_date'] ?? $need->needed_by_date,
                    'status' => InventoryOrderItemStatus::PENDING,
                    'currency' => $supplier->currency ?? 'USD',
                ]);
                abort_unless($detail->save(), 409, 'Não foi possível criar os itens do pedido.');
                abort_unless($item->update(['status' => 'ordered']), 409, 'Não foi possível actualizar os itens da necessidade.');
                $totalAmount += $quantity * $unitPrice;
            }
            abort_unless($order->update(['total_amount' => $totalAmount]), 409, 'Não foi possível guardar o valor do pedido.');
            abort_unless($need->update(['status' => 'ordered', 'inventory_order_id' => $order->id]), 409, 'Não foi possível associar o pedido à necessidade.');
            $this->access->operator($userId, $labId, 'add_iorders');

            return $order;
        });
    }
}
