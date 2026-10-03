<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\StaffAccountAccess;
use App\Support\ReportStudioAssetLibrary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaStudioUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = User::query()->find($this->user()?->id);

        return $actor !== null && app(StaffAccountAccess::class)->isSystemAdministrator($actor);
    }

    /** @return array<string,array<mixed>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120', 'mimetypes:image/svg+xml,image/png,image/jpeg,image/webp,image/gif,image/avif'],
            'studio_asset_context' => ['nullable', 'string', Rule::in(['report_studio', 'proposal_studio'])],
            'studio_asset_kind' => ['nullable', 'string', Rule::in(ReportStudioAssetLibrary::studioUploadKinds())],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Seleccione um ficheiro para carregar.',
            'file.mimetypes' => 'Use SVG, PNG, JPEG, WebP, GIF ou AVIF para media de estúdio.',
        ];
    }
}
