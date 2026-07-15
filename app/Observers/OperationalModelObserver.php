<?php

namespace App\Observers;

use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Support\NotificationTemplateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class OperationalModelObserver
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function created(Model $model): void
    {
        $notification = match (true) {
            $model instanceof Invoice => ['commercial.invoice.created', $this->commercialContext($model, $model->inv_no, '/invoices/'.$model->id.'/show')],
            $model instanceof Quote => ['commercial.quote.created', $this->commercialContext($model, $model->quote_no, '/quotes/'.$model->id.'/show')],
            $model instanceof CreditNote => ['commercial.credit_note.created', $this->commercialContext($model, $model->note_no, '/creditnotes')],
            $model instanceof Receipt => ['commercial.receipt.created', $this->commercialContext($model, $model->rec_no, '/receipts')],
            $model instanceof ImportCertificate => ['trade.import_certificate.created', $this->certificateContext($model, $model->cert_no, '/import-certificates/'.$model->id)],
            $model instanceof ExportCertificate => ['trade.export_certificate.created', $this->certificateContext($model, $model->cert_no, '/export-certificates/'.$model->id)],
            default => null,
        };

        $this->dispatch($model, $notification);
    }

    public function updated(Model $model): void
    {
        $notification = match (true) {
            $model instanceof Invoice && $model->wasChanged(['amount_due', 'status', 'status_code']) && $model->isPaid() => [
                'commercial.invoice.paid',
                $this->commercialContext($model, $model->inv_no, '/invoices/'.$model->id.'/show'),
            ],
            $model instanceof Quote && $model->wasChanged('converted_to_invoice') && $model->converted_to_invoice => [
                'commercial.quote.converted',
                $this->commercialContext($model, $model->quote_no, '/quotes/'.$model->id.'/show'),
            ],
            $model instanceof QualityCertificate && $model->wasChanged(['validated_at', 'status']) && ($model->validated_at || $model->status) => [
                'quality.certificate.validated',
                $this->certificateContext($model, $model->code, '/qualitycertificates/'.$model->id),
            ],
            default => null,
        };

        $this->dispatch($model, $notification);
    }

    /**
     * @param  array{0: string, 1: array<string, scalar|null>}|null  $notification
     */
    private function dispatch(Model $model, ?array $notification): void
    {
        if (! $notification) {
            return;
        }

        [$key, $context] = $notification;
        $this->templates->notifyPermission($key, $context, auth()->id() ?: $model->getAttribute('user_id'));
    }

    /** @return array<string, scalar|null> */
    private function commercialContext(Model $model, ?string $number, string $path): array
    {
        $model->loadMissing('customer');

        return [
            'document_number' => $number ?: '#'.$model->getKey(),
            'customer_name' => $model->customer?->name ?? 'Cliente',
            'total' => $model->getAttribute('total') !== null ? Number::currency((float) $model->getAttribute('total'), 'AOA', 'pt_PT') : '',
            'document_url' => url($path),
        ];
    }

    /** @return array<string, scalar|null> */
    private function certificateContext(Model $model, ?string $number, string $path): array
    {
        $customer = $model instanceof ImportCertificate ? $model->importer : ($model instanceof ExportCertificate ? $model->exporter : $model->customer);

        return [
            'document_number' => $number ?: '#'.$model->getKey(),
            'customer_name' => $customer?->name ?? 'Cliente',
            'document_url' => url($path),
        ];
    }
}
