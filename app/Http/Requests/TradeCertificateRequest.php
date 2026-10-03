<?php

namespace App\Http\Requests;

use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Services\TradeCertificateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class TradeCertificateRequest extends FormRequest
{
    abstract protected function certificateKind(): string;

    public function authorize(): bool
    {
        return $this->user()?->can(($this->isMethod('post') ? 'add_' : 'edit_').$this->certificateKind().'_certificates') ?? false;
    }

    private function observationsOnly(): bool
    {
        if ($this->isMethod('post')) {
            return false;
        }
        $class = $this->certificateKind() === 'import' ? ImportCertificate::class : ExportCertificate::class;
        $record = $class::query()->findOrFail($this->route($this->certificateKind().'certificate'));

        return $record->invoice_id !== null || (bool) $record->invoiced;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return app(TradeCertificateData::class)->rules($this->certificateKind(), $this->observationsOnly());
    }

    protected function prepareForValidation(): void
    {
        $this->replace(app(TradeCertificateData::class)->normalize($this->certificateKind(), $this->all()));
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->observationsOnly()) {
                app(TradeCertificateData::class)->rejectLockedFields($validator, $this->all());
            }
        }];
    }
}
