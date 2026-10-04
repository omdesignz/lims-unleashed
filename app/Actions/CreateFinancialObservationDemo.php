<?php

namespace App\Actions;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Permission\Models\Permission;

class CreateFinancialObservationDemo
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Financial demonstration data is restricted to local and testing environments.');
        }

        return DB::transaction(function (): array {
            $marker = 'DEMO-OBS-'.Str::ulid();
            $notice = 'DEMONSTRAÇÃO — documento fictício, sem valor fiscal. Não enviar.';
            $password = Str::random(40);
            $staff = $this->create(User::class, ['name' => $marker.' Editor', 'email' => Str::lower($marker).'@example.test',
                'password' => Hash::make($password), 'is_active' => true, 'email_verified_at' => now()]);
            foreach (['invoices', 'credit_notes', 'receipts', 'quotes', 'import_certificates', 'export_certificates'] as $module) {
                foreach (['view', 'edit'] as $ability) {
                    $staff->givePermissionTo(Permission::findOrCreate($ability.'_'.$module, 'web'));
                }
            }
            $lab = $this->create(VAPLab::class, ['name' => $marker.' Laboratório', 'code' => $marker, 'description' => $notice]);
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $staff->id,
                'can_view_network' => false, 'can_manage_branding' => false, 'created_at' => now(), 'updated_at' => now()]);
            $customer = $this->create(Customer::class, ['name' => $marker.' Cliente', 'description' => $notice]);
            $site = $this->create(Warehouse::class, ['name' => $marker.' Local', 'customer_id' => $customer->id, 'description' => $notice]);
            $owner = ['lab_id' => $lab->id, 'user_id' => $staff->id, 'customer_id' => $customer->id, 'warehouse_id' => $site->id];
            $financial = [...$owner, 'date' => today()->toDateString(), 'obs' => $notice,
                'total' => '1250.00', 'sub_total' => '1250.00'];
            $invoice = $this->create(Invoice::class, [...$financial, 'inv_no' => $marker.'-FT',
                'invoice_month' => today()->format('m/Y'), 'due_date' => today()->addDays(30)->toDateString(), 'amount_due' => '0.00']);
            $quote = $this->create(Quote::class, [...$financial, 'quote_no' => $marker.'-PF',
                'quote_month' => today()->format('m/Y'), 'invoice_id' => $invoice->id, 'converted_to_invoice' => true]);
            $note = $this->create(CreditNote::class, [...$financial, 'note_no' => $marker.'-NC',
                'note_month' => today()->format('m/Y'), 'invoice_id' => $invoice->id, 'reason' => 'R']);
            foreach ([[InvoiceItem::class, 'invoice_id', $invoice], [QuoteItem::class, 'quote_id', $quote],
                [CreditNoteItem::class, 'note_id', $note]] as [$model, $foreignKey, $document]) {
                $this->create($model, [$foreignKey => $document->id, 'lab_id' => $lab->id,
                    'item_description' => $notice, 'qty' => 1, 'unit_price' => '1250.00', 'total' => '1250.00', 'charge_tax' => false]);
            }
            $receipt = $this->create(Receipt::class, [...$owner, 'date' => today()->toDateString(), 'obs' => $notice,
                'description' => $notice, 'rec_no' => $marker.'-RG', 'rec_month' => today()->format('m/Y')]);
            $this->create(InvoiceReceipt::class, ['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id,
                'lab_id' => $lab->id, 'paid_amount' => '1250.00']);
            $trade = ['lab_id' => $lab->id, 'user_id' => $staff->id, 'date' => today()->toDateString(),
                'obs' => $notice, 'invoice_id' => $invoice->id, 'invoiced' => true,
                'exporter_id' => $customer->id, 'exporter_warehouse_id' => $site->id];
            $import = $this->create(ImportCertificate::class, [...$trade, 'cert_no' => $marker.'-CI',
                'importer_id' => $customer->id, 'importer_warehouse_id' => $site->id]);
            $export = $this->create(ExportCertificate::class, [...$trade, 'cert_no' => $marker.'-CE']);

            return ['marker' => $marker, 'lab_id' => $lab->id,
                'staff' => ['id' => $staff->id, 'email' => $staff->email, 'password' => $password],
                'documents' => ['invoice' => $invoice->id, 'credit_note' => $note->id, 'receipt' => $receipt->id,
                    'quote' => $quote->id, 'import_certificate' => $import->id, 'export_certificate' => $export->id]];
        });
    }

    /**
     * Synthetic records must not allocate issued sequences, sign, or notify.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function create(string $model, array $attributes): Model
    {
        $record = new $model;
        $record->forceFill($attributes);
        if (! $record->saveQuietly()) {
            throw new LogicException('Financial demonstration creation failed; all fixture writes were rolled back.');
        }

        return $record;
    }
}
