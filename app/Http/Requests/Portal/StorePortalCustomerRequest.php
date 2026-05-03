<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortalCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('portal')->check();
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', Rule::in([
                'general_support',
                'complaint',
                'analysis_request',
                'collection_request',
                'document_request',
                'billing_support',
                'certificate_support',
            ])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'email' => ['required', 'email', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'category_id' => ['nullable', 'exists:customer_request_categories,id'],
            'details' => ['nullable', 'array'],
            'details.sample_name' => ['nullable', 'string', 'max:255'],
            'details.matrix' => ['nullable', 'string', 'max:255'],
            'details.matrix_id' => ['nullable', 'integer', 'exists:matrixes,id'],
            'details.product_name' => ['nullable', 'string', 'max:255'],
            'details.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'details.lot' => ['nullable', 'string', 'max:255'],
            'details.packaging' => ['nullable', 'string', 'max:255'],
            'details.packaging_id' => ['nullable', 'integer', 'exists:packaging_categories,id'],
            'details.quantity' => ['nullable', 'string', 'max:255'],
            'details.notes' => ['nullable', 'string', 'max:2000'],
            'details.collection_required' => ['nullable', 'boolean'],
            'details.requested_profiles' => ['nullable', 'array'],
            'details.requested_profiles.*' => ['integer', 'exists:profiles,id'],
            'details.samples' => ['nullable', 'array'],
            'details.samples.*.sample_name' => ['nullable', 'string', 'max:255'],
            'details.samples.*.product_name' => ['nullable', 'string', 'max:255'],
            'details.samples.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'details.samples.*.matrix' => ['nullable', 'string', 'max:255'],
            'details.samples.*.matrix_id' => ['nullable', 'integer', 'exists:matrixes,id'],
            'details.samples.*.lot' => ['nullable', 'string', 'max:255'],
            'details.samples.*.packaging' => ['nullable', 'string', 'max:255'],
            'details.samples.*.packaging_id' => ['nullable', 'integer', 'exists:packaging_categories,id'],
            'details.samples.*.quantity' => ['nullable', 'string', 'max:255'],
            'details.samples.*.notes' => ['nullable', 'string', 'max:2000'],
            'details.collection_location' => ['nullable', 'string', 'max:255'],
            'details.collection_address' => ['nullable', 'string', 'max:500'],
            'details.collection_contact_name' => ['nullable', 'string', 'max:255'],
            'details.collection_contact_phone' => ['nullable', 'string', 'max:255'],
            'details.preferred_time_window' => ['nullable', 'string', 'max:255'],
            'details.items' => ['nullable', 'array'],
            'details.items.*.name' => ['nullable', 'string', 'max:255'],
            'details.items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'details.items.*.lot' => ['nullable', 'string', 'max:255'],
            'details.document_reference' => ['nullable', 'string', 'max:255'],
            'details.document_type' => ['nullable', 'string', 'max:255'],
            'details.invoice_reference' => ['nullable', 'string', 'max:255'],
            'details.certificate_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $requestType = $this->input('request_type');
                $details = $this->input('details', []);

                if ($requestType === 'analysis_request' && blank($details['requested_profiles'] ?? null)) {
                    $validator->errors()->add('details.requested_profiles', 'Selecione pelo menos uma análise solicitada.');
                }

                if ($requestType === 'analysis_request') {
                    $hasSingleSampleDetails = filled($details['sample_name'] ?? null)
                        || filled($details['product_name'] ?? null)
                        || filled($details['matrix'] ?? null);

                    $samples = collect($details['samples'] ?? [])
                        ->filter(function ($sample) {
                            return filled($sample['sample_name'] ?? null)
                                || filled($sample['product_name'] ?? null)
                                || filled($sample['matrix'] ?? null)
                                || filled($sample['lot'] ?? null);
                        })
                        ->values();

                    if (! $hasSingleSampleDetails && $samples->isEmpty()) {
                        $validator->errors()->add('details.sample_name', 'Informe pelo menos uma amostra ou carregue um lote de amostras.');
                    }
                }

                if ($requestType === 'collection_request') {
                    if (blank($details['collection_location'] ?? null) && blank($details['collection_address'] ?? null)) {
                        $validator->errors()->add('details.collection_location', 'Informe o local ou endereço pretendido para a colheita.');
                    }

                    if (blank($details['items'] ?? null)) {
                        $validator->errors()->add('details.items', 'Adicione pelo menos um item para a colheita.');
                    }
                }

                if ($requestType === 'complaint' && blank($this->input('description'))) {
                    $validator->errors()->add('description', 'Descreva a reclamação para registo formal.');
                }
            },
        ];
    }
}
