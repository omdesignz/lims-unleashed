<?php

namespace App\Actions;

use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;

class SetBillingDocumentsArchived
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /**
     * @param  class-string<Invoice|CreditNote|Receipt|Quote|ImportCertificate|ExportCertificate>  $class
     * @param  list<int>  $recordIds
     */
    public function execute(int $userId, int $labId, string $class, array $recordIds, bool $archived): int
    {
        $module = match ($class) {
            Invoice::class => 'invoices',
            CreditNote::class => 'credit_notes',
            Receipt::class => 'receipts',
            Quote::class => 'quotes',
            ImportCertificate::class => 'import_certificates',
            ExportCertificate::class => 'export_certificates',
            default => throw new LogicException('Unsupported billing archive source.'),
        };
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();
        $permission = ($archived ? 'delete_' : 'restore_').$module;

        return DB::transaction(function () use ($userId, $labId, $class, $recordIds, $archived, $permission): int {
            $operator = $this->access->operator($userId, $labId, $permission);
            $records = $class::withoutGlobalScope('financial_laboratory')->withTrashed()
                ->where('lab_id', $labId)->whereKey($recordIds)->orderBy('id')->lockForUpdate()->get();
            abort_unless($records->count() === count($recordIds), 404);

            $snapshots = [];
            foreach ($records as $record) {
                $snapshots[$record->id] = [
                    'core' => Arr::except($record->getAttributes(), ['deleted_at', 'updated_at']),
                    'items' => $this->items($record, true),
                ];
            }

            $changed = 0;
            $audits = [];
            foreach ($records as $record) {
                if ($record->trashed() === $archived) {
                    continue;
                }
                abort_unless($archived ? $record->delete() : $record->restore(), 409, 'Não foi possível actualizar o arquivo do documento.');
                $this->assertPreserved($record, $snapshots[$record->id], $archived);
                $audit = activity()->causedBy($operator)->performedOn($record)
                    ->event($archived ? 'archived' : 'restored')
                    ->withProperties(['lab_id' => $labId, 'billing_archive' => true])
                    ->log($archived ? 'Arquivou o documento comercial.' : 'Restaurou o documento comercial.');
                abort_unless($audit, 409, 'Não foi possível registar o histórico do documento.');
                $this->assertAuditPersisted($audit, $operator, $record, $labId, $archived);
                $audits[$record->id] = $audit;
                $this->assertPreserved($record, $snapshots[$record->id], $archived);
                $changed++;
            }
            foreach ($records as $record) {
                $this->assertPreserved($record, $snapshots[$record->id], $archived);
                if (isset($audits[$record->id])) {
                    $this->assertAuditPersisted($audits[$record->id], $operator, $record, $labId, $archived);
                }
            }
            $this->access->operator($userId, $labId, $permission);

            return $changed;
        }, 3);
    }

    /** @return list<array<string, mixed>> */
    private function items(Model $record, bool $lock = false): array
    {
        return $record->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')
            ->when($lock, fn ($query) => $query->lockForUpdate())->get()
            ->map(fn (Model $item): array => $item->getAttributes())->all();
    }

    /** @param array{core: array<string, mixed>, items: list<array<string, mixed>>} $before */
    private function assertPreserved(Model $record, array $before, bool $archived): void
    {
        $persisted = $record->newQueryWithoutScopes()->find($record->id);
        abort_unless($persisted && $persisted->trashed() === $archived
            && Arr::except($persisted->getAttributes(), ['deleted_at', 'updated_at']) === $before['core']
            && $this->items($persisted) === $before['items'], 409, 'O conteúdo do documento mudou durante a operação de arquivo.');
    }

    private function assertAuditPersisted(Model $audit, User $operator, Model $record, int $labId, bool $archived): void
    {
        $persisted = $audit->newQueryWithoutScopes()->find($audit->id);
        abort_unless($persisted && $persisted->subject_type === $record->getMorphClass()
            && (int) $persisted->subject_id === (int) $record->id
            && $persisted->causer_type === $operator->getMorphClass()
            && (int) $persisted->causer_id === (int) $operator->id
            && $persisted->event === ($archived ? 'archived' : 'restored')
            && (int) $persisted->properties->get('lab_id') === $labId
            && $persisted->properties->get('billing_archive') === true,
            409, 'Não foi possível registar o histórico do documento.');
    }
}
