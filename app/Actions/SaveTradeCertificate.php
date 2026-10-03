<?php

namespace App\Actions;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\PhytosanitaryProduct;
use App\Models\TransportCategory;
use App\Models\Warehouse;
use App\Services\IssuedFinancialDocumentIntegrity;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\TradeCertificateData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class SaveTradeCertificate
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly TradeCertificateData $data, private readonly IssuedFinancialDocumentIntegrity $integrity) {}

    /** @param array<string, mixed> $input */
    public function execute(int $userId, int $labId, string $kind, array $input, ?int $id = null): ImportCertificate|ExportCertificate
    {
        [$class, $lineClass] = match ($kind) {
            'import' => [ImportCertificate::class, ImportCertificateItem::class],
            'export' => [ExportCertificate::class, ExportCertificateItem::class],
            default => throw new LogicException('Unsupported trade certificate kind.'),
        };
        $permission = ($id === null ? 'add_' : 'edit_').$kind.'_certificates';

        return DB::transaction(function () use ($userId, $labId, $kind, $input, $id, $class, $lineClass, $permission): ImportCertificate|ExportCertificate {
            $operator = $this->access->operator($userId, $labId, $permission);
            $record = $id === null ? new $class : $class::query()->where('lab_id', $labId)->lockForUpdate()->findOrFail($id);
            $locked = $record->exists && ($record->invoice_id !== null || (bool) $record->invoiced);
            $attributes = $this->data->validate($kind, $input, $locked);
            $lines = $record->exists ? $record->items()->withTrashed()->orderBy('id')->lockForUpdate()->get() : collect();
            $lineEvidence = $lines->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all();
            if (! $locked) {
                $this->lockActiveDirectories($kind, $attributes);
            }
            $record->fill(Arr::except($attributes, ['items']));
            if ($id === null) {
                $record->forceFill(['lab_id' => $labId, 'user_id' => $operator->id, 'invoice_id' => null, 'invoiced' => false]);
            }
            $intended = clone $record;
            unset($intended->updated_at);
            if (! $record->save()) {
                throw new LogicException('Trade certificate authoring was not persisted.');
            }
            $persisted = $record->fresh();
            $this->integrity->assertCreationIntent($intended, $persisted, ['vat', 'vat_cost', 'cost_freight', 'cost_insurance', 'cost_final']);
            if ($id === null && $record->issuedTradeCertificateNumber() !== ['cert_no' => $persisted->cert_no,
                'certificate_year' => (int) $persisted->certificate_year, 'seq' => (int) $persisted->seq]) {
                throw new LogicException('Trade certificate issued identity changed during creation.');
            }
            $rootEvidence = Arr::except($persisted->getAttributes(), ['updated_at']);
            if (! $locked) {
                foreach ($lines->reject(fn (Model $line): bool => $line->trashed()) as $line) {
                    $before = Arr::except($line->getAttributes(), ['deleted_at', 'updated_at']);
                    if (! $line->delete()) {
                        throw new LogicException('Trade certificate line retirement was not persisted.');
                    }
                    $retired = $line->fresh();
                    if (! $retired->trashed() || Arr::except($retired->getAttributes(), ['deleted_at', 'updated_at']) !== $before) {
                        throw new LogicException('Retained trade certificate line changed during retirement.');
                    }
                    $lineEvidence[$line->id] = $retired->getAttributes();
                }
                foreach ($attributes['items'] as $item) {
                    $line = new $lineClass(Arr::only($item, $this->data->lineFields($kind)));
                    $line->forceFill(['certificate_id' => $record->id, 'lab_id' => $labId]);
                    $intendedLine = clone $line;
                    if (! $line->save()) {
                        throw new LogicException('Trade certificate line authoring was not persisted.');
                    }
                    $persistedLine = $line->fresh();
                    $this->integrity->assertCreationIntent($intendedLine, $persistedLine, ['qty']);
                    $lineEvidence[$line->id] = $persistedLine->getAttributes();
                }
            }
            $properties = ['lab_id' => $labId, 'operation' => $id === null ? 'create' : ($locked ? 'observations' : 'update'),
                'line_ids' => array_keys($lineEvidence)];
            $audit = activity()->causedBy($operator)->performedOn($record)->event('authored')->withProperties($properties)->log('Guardou o certificado comercial.');
            $audit = $audit?->newQueryWithoutScopes()->find($audit->id);
            if (! $audit || $audit->subject_type !== $record->getMorphClass() || (int) $audit->subject_id !== (int) $record->id
                || $audit->causer_type !== $operator->getMorphClass() || (int) $audit->causer_id !== $userId
                || $audit->event !== 'authored' || $audit->properties->all() !== $properties
                || Arr::except($record->newQueryWithoutScopes()->findOrFail($record->id)->getAttributes(), ['updated_at']) !== $rootEvidence
                || $record->items()->withoutGlobalScope('financial_laboratory')->withTrashed()->orderBy('id')->get()
                    ->mapWithKeys(fn (Model $line): array => [$line->id => $line->getAttributes()])->all() !== $lineEvidence) {
                throw new LogicException('Trade certificate, retained lines or audit changed during authoring.');
            }
            $this->access->operator($userId, $labId, $permission);
            if (! $locked) {
                $this->lockActiveDirectories($kind, $attributes);
            }

            return $record->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    private function lockActiveDirectories(string $kind, array $attributes): void
    {
        foreach ($kind === 'import' ? ['exporter', 'importer'] : ['exporter'] as $party) {
            if (! Customer::query()->whereKey($attributes[$party.'_id'])->sharedLock()->first()
                || ! Warehouse::query()->whereKey($attributes[$party.'_warehouse_id'])->where('customer_id', $attributes[$party.'_id'])->sharedLock()->first()) {
                throw ValidationException::withMessages([$party.'_warehouse_id' => 'O local deve pertencer à entidade seleccionada.']);
            }
        }
        $directories = ['trans_type_id' => TransportCategory::class];
        $directories += $kind === 'import' ? ['destination_country_id' => Country::class, 'currency_id' => Currency::class]
            : ['country_origin_id' => Country::class, 'country_destination_id' => Country::class];
        foreach ($directories as $field => $class) {
            if (($attributes[$field] ?? null) !== null && ! $class::query()->whereKey($attributes[$field])->sharedLock()->first()) {
                throw ValidationException::withMessages([$field => 'O registo seleccionado já não está disponível.']);
            }
        }
        $ids = collect($attributes['items'])->pluck('product_id')->unique()->sort()->values();
        if (PhytosanitaryProduct::query()->whereKey($ids)->orderBy('id')->sharedLock()->get()->count() !== $ids->count()) {
            throw ValidationException::withMessages(['items' => 'Um produto seleccionado já não está disponível.']);
        }
    }
}
