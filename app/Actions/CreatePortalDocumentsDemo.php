<?php

namespace App\Actions;

use App\Models\CollectionProduct;
use App\Models\ContractGuide;
use App\Models\ContractGuideItem;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\LabCode;
use App\Models\Product;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receipt;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class CreatePortalDocumentsDemo
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Portal demonstration data is restricted to local and testing environments.');
        }

        return DB::transaction(function (): array {
            $marker = 'DEMO-PORTAL-'.Str::ulid();
            $notice = 'DEMONSTRAÇÃO — documento fictício, sem valor fiscal ou laboratorial.';
            $author = $this->create(User::class, ['name' => $marker.' Autor', 'email' => Str::lower($marker).'@example.test',
                'password' => Hash::make(Str::random(40)), 'is_active' => false]);
            $lab = $this->create(VAPLab::class, ['name' => $marker.' Laboratório', 'code' => $marker, 'description' => $notice]);
            $customer = $this->create(Customer::class, ['name' => $marker.' Cliente', 'description' => $notice]);
            $password = Str::random(40);
            $site = $this->create(Warehouse::class, ['name' => $marker.' Local', 'customer_id' => $customer->id,
                'email' => Str::lower($marker).'-portal@example.test', 'password' => Hash::make($password),
                'email_verified_at' => now(), 'description' => $notice]);
            $peer = $this->create(Warehouse::class, ['name' => $marker.' Outro local', 'customer_id' => $customer->id,
                'description' => $notice]);
            $product = $this->create(Product::class, ['name' => $marker.' Água de demonstração', 'description' => $notice]);
            $owner = ['customer_id' => $customer->id, 'warehouse_id' => $site->id, 'user_id' => $author->id];
            $financial = [...$owner, 'lab_id' => $lab->id, 'date' => today()->toDateString(), 'description' => $notice,
                'obs' => $notice, 'total' => '1250.00', 'sub_total' => '1250.00'];
            $invoice = $this->create(Invoice::class, [...$financial, 'inv_no' => $marker.'-FT',
                'invoice_month' => today()->format('m/Y'), 'due_date' => today()->addDays(30)->toDateString(),
                'amount_due' => '0.00', 'status' => true]);
            $quote = $this->create(Quote::class, [...$financial, 'quote_no' => $marker.'-PF',
                'quote_month' => today()->format('m/Y'), 'due_date' => today()->addDays(30)->toDateString()]);
            $note = $this->create(CreditNote::class, [...array_diff_key($financial, ['description' => true]), 'note_no' => $marker.'-NC',
                'note_month' => today()->format('m/Y'), 'invoice_id' => $invoice->id, 'reason' => 'R']);
            foreach ([[InvoiceItem::class, 'invoice_id', $invoice], [QuoteItem::class, 'quote_id', $quote],
                [CreditNoteItem::class, 'note_id', $note]] as [$model, $foreignKey, $document]) {
                $this->create($model, [$foreignKey => $document->id, 'lab_id' => $lab->id,
                    'item_description' => 'Análise fictícia — apenas demonstração', 'qty' => 1,
                    'unit_price' => '1250.00', 'total' => '1250.00', 'charge_tax' => false]);
            }
            $receipt = $this->create(Receipt::class, [...$owner, 'lab_id' => $lab->id, 'rec_no' => $marker.'-RG',
                'rec_month' => today()->format('m/Y'), 'date' => today()->toDateString(), 'description' => $notice]);
            $this->create(InvoiceReceipt::class, ['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id,
                'lab_id' => $lab->id, 'paid_amount' => '1250.00']);
            $guide = $this->create(ContractGuide::class, [...$owner, 'guide_no' => $marker.'-GC',
                'guide_month' => today()->format('m/Y'), 'date' => today()->toDateString(),
                'contact' => 'Contacto fictício', 'obs' => $notice, 'du_no' => 'DEMO-DU', 'bl' => 'DEMO-BL']);
            $this->create(ContractGuideItem::class, ['guide_id' => $guide->id, 'product_id' => $product->id,
                'lot' => 'DEMO-LOT', 'obs' => $notice]);
            $certificate = $this->certificate($marker.'-BA', $owner, $lab, $invoice, $product, $notice, true);
            $unreleased = $this->certificate($marker.'-HIDDEN', $owner, $lab, $invoice, $product, $notice, false);
            $foreign = $this->create(Invoice::class, [...$financial, 'warehouse_id' => $peer->id,
                'inv_no' => $marker.'-PRIVATE', 'invoice_month' => today()->format('m/Y')]);

            return ['marker' => $marker, 'lab_id' => $lab->id,
                'portal' => ['id' => $site->id, 'email' => $site->email, 'password' => $password],
                'documents' => ['invoice' => $invoice->id, 'quote' => $quote->id, 'credit_note' => $note->id,
                    'receipt' => $receipt->id, 'contract_guide' => $guide->id, 'quality_certificate' => $certificate->id],
                'hidden' => ['invoice' => $foreign->id, 'quality_certificate' => $unreleased->id]];
        });
    }

    /** @param array<string, int> $owner */
    private function certificate(string $number, array $owner, VAPLab $lab, Invoice $invoice, Product $product, string $notice, bool $released): QualityCertificate
    {
        $site = array_intersect_key($owner, array_flip(['customer_id', 'warehouse_id']));
        $collection = $this->create(CollectionProduct::class, [...$site, 'invoice_id' => $invoice->id, 'product_id' => $product->id]);
        $this->create(VAPSampleEntry::class, [...$site, 'lab_id' => $lab->id, 'collection_product_id' => $collection->id,
            'name' => $notice, 'code' => $number.'-AM', 'received_at' => now()]);
        $code = $this->create(LabCode::class, ['collection_id' => $collection->id, 'code' => $number.'-CL',
            'cl_month' => today()->format('Y'), 'codeable_type' => 'analysis']);
        $sample = $this->create(Sample::class, ['cl_id' => $code->id, 'code' => $number.'-SP', 'sample_month' => today()->format('Y')]);
        $this->create(Result::class, ['sample_id' => $sample->id, 'code_id' => $code->id, 'collection_id' => $collection->id,
            'product_id' => $product->id, 'parameter_label' => 'pH de demonstração', 'unit_label' => 'pH',
            'inserted_value' => '7.10', 'verified_value' => '7.10', 'approved_value' => '7.10',
            'inserted_date' => now(), 'verified_date' => now(), 'approved_date' => now(), 'approval_notes' => $notice]);

        return $this->create(QualityCertificate::class, [...$owner, 'collection_id' => $collection->id,
            'cl_id' => $code->id, 'product_id' => $product->id, 'code' => $number, 'obs' => $notice,
            'validated_at' => $released ? now() : null]);
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
            throw new LogicException('Portal demonstration creation failed; all fixture writes were rolled back.');
        }

        return $record;
    }
}
