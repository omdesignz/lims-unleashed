<?php

namespace App\Actions;

use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\ProposalNotificationOwnership;
use App\Support\ProposalAuthoringPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProposal
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly ProposalNotificationOwnership $ownership,
        private readonly ProposalAuthoringPayload $payload
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $labId, int $userId, array $data): VAPProposal
    {
        return DB::transaction(function () use ($labId, $userId, $data): VAPProposal {
            $operator = $this->access->operator($userId, $labId, 'add_proposals');
            $validated = $this->payload->validate($data, true);
            if (! VAPProposalTemplate::query()->whereKey($validated['template_id'])->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['template_id' => 'O modelo de proposta já não está disponível.']);
            }
            $items = $this->payload->normalizeItems($validated['items']);
            $totals = $this->payload->totals($items);
            $proposal = new VAPProposal([
                'proposal_year' => now()->year,
                'service_location' => $validated['service_location'],
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'department_id' => $validated['department_id'],
                'template_id' => $validated['template_id'],
                'user_id' => $operator->id, 'status' => 'PENDING', 'details' => [],
                'obs' => $validated['obs'] ?? null, 'sub_total' => $totals['sub_total'], 'total' => $totals['total'],
                'unique_hash' => (string) str()->uuid(), 'tolerance_days' => $validated['tolerance_days'],
                'withhold_tax' => $validated['withhold_tax'] ?? false, 'use_matrix_price' => $validated['use_matrix_price'] ?? true,
                'withholding_tax_amount' => 0, 'withholding_tax_percentage' => 0,
                'global_discount_amount' => 0, 'global_discount_percentage' => 0, 'converted_to_invoice' => false,
            ]);
            $proposal->lab_id = $labId;
            $sourceContext = $this->ownership->context($proposal);
            abort_unless($proposal->save(), 409, 'Não foi possível criar a proposta.');
            foreach ($items as $item) {
                abort_unless($proposal->items()->make($item)->save(), 409, 'Não foi possível registar os itens da proposta.');
            }
            abort_unless($proposal->complianceAgreement()->make([
                'confidentiality' => false, 'impartiality' => false, 'nondisclosure' => false,
            ])->save(), 409, 'Não foi possível criar o acordo da proposta.');
            $audit = activity()->causedBy($operator)->performedOn($proposal)->withProperties(['lab_id' => $labId])->log('created_by_staff');
            abort_unless($audit?->exists, 409, 'Não foi possível registar o histórico da proposta.');
            $this->payload->validate($validated, true);
            $this->access->operator($userId, $labId, 'add_proposals');
            abort_unless($this->ownership->resolve([...$sourceContext, 'proposal_id' => $proposal->id]), 404);
            abort_unless((int) VAPProposal::withoutGlobalScope('proposal_laboratory')->whereKey($proposal->id)->value('template_id') === (int) $validated['template_id'],
                409, 'O modelo da proposta guardada não corresponde ao modelo seleccionado.');

            return $proposal;
        }, 3);
    }
}
