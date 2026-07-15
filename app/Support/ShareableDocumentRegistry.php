<?php

namespace App\Support;

use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ShareableDocumentRegistry
{
    public function __construct(
        private readonly ReportStudioPdfBuilder $builder,
        private readonly ReportStudioPdfRenderer $renderer,
        private readonly GeneralSettings $settings
    ) {}

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->definitions());
    }

    /** @return array<string, mixed>|null */
    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    /**
     * @return array{content: string, filename: string, label: string, number: string, url: string, default_recipients: array<int, string>}
     */
    public function render(string $key, int $id): array
    {
        $definition = $this->definition($key) ?? throw new InvalidArgumentException('Unsupported document type.');
        /** @var Model $model */
        $model = $definition['model']::query()->with($definition['relations'])->findOrFail($id);
        $payload = $this->builder->{$definition['builder']}($model, $this->settings);
        $number = (string) ($model->getAttribute($definition['number']) ?: '#'.$model->getKey());
        $filename = str($definition['label'].'-'.$number)->slug('-')->append('.pdf')->toString();
        $rendered = $this->renderer->renderDocument($definition['renderer'], $payload, $filename);

        return [
            'content' => $rendered['content'],
            'filename' => $filename,
            'label' => $definition['label'],
            'number' => $number,
            'url' => url($definition['url']($model)),
            'default_recipients' => $this->defaultRecipients($model, $definition['recipientRelation']),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            'invoice' => $this->document(Invoice::class, 'view_invoices', 'Factura', 'inv_no', 'buildInvoicePayload', 'invoice', ['items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'invoice_category', 'user'], 'warehouse', fn (Model $model) => '/invoices/'.$model->id.'/show'),
            'quote' => $this->document(Quote::class, 'view_quotes', 'Cotação', 'quote_no', 'buildQuotePayload', 'quote', ['items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'user'], 'warehouse', fn (Model $model) => '/quotes/'.$model->id.'/show'),
            'credit_note' => $this->document(CreditNote::class, 'view_credit_notes', 'Nota de crédito', 'note_no', 'buildCreditNotePayload', 'credit_note', ['items', 'user', 'customer', 'warehouse', 'invoice'], 'warehouse', fn (Model $model) => '/creditnotes'),
            'receipt' => $this->document(Receipt::class, 'view_receipts', 'Recibo', 'rec_no', 'buildReceiptPayload', 'receipt', ['items.invoice', 'user', 'customer', 'warehouse'], 'warehouse', fn (Model $model) => '/receipts'),
            'import_certificate' => $this->document(ImportCertificate::class, 'view_import_certificates', 'Certificado de importação', 'cert_no', 'buildImportCertificatePayload', 'import_certificate', ['items.product', 'destination_country', 'trans', 'currency', 'importer', 'importer_warehouse', 'exporter', 'exporter_warehouse', 'user'], 'importer_warehouse', fn (Model $model) => '/import-certificates/'.$model->id),
            'export_certificate' => $this->document(ExportCertificate::class, 'view_export_certificates', 'Certificado de exportação', 'cert_no', 'buildExportCertificatePayload', 'export_certificate', ['items.product', 'country_destination', 'country_origin', 'trans_type', 'exporter', 'exporter_warehouse', 'user'], 'exporter_warehouse', fn (Model $model) => '/export-certificates/'.$model->id),
            'quality_certificate' => $this->document(QualityCertificate::class, 'view_quality_certificates', 'Boletim analítico', 'code', 'buildAnalysisReportPayload', 'analysis', ['collection', 'lab_code', 'user', 'customer', 'warehouse'], 'warehouse', fn (Model $model) => '/qualitycertificates/'.$model->id),
        ];
    }

    /** @return array<string, mixed> */
    private function document(string $model, string $permission, string $label, string $number, string $builder, string $renderer, array $relations, string $recipientRelation, callable $url): array
    {
        return compact('model', 'permission', 'label', 'number', 'builder', 'renderer', 'relations', 'recipientRelation', 'url');
    }

    /** @return array<int, string> */
    private function defaultRecipients(Model $model, string $relation): array
    {
        $recipient = $model->getRelation($relation);

        return collect(Arr::wrap([$recipient?->invoicing_email, $recipient?->email, $recipient?->focal_point_email]))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
