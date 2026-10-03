<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(isset(app(NotificationTemplateCatalog::class)->definitions()[$this->route('key')]), 404);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        return app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->whereKey($this->user()?->id)->first()?->can('edit_settings') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->rulesForKey((string) $this->route('key'));
    }

    /** @return array<string, mixed> */
    public function rulesForKey(string $key): array
    {
        $definition = app(NotificationTemplateCatalog::class)->definitions()[$key] ?? null;
        abort_unless($definition, 404);
        $variables = [...$definition['variables'], 'lab_name', 'actor_name'];
        $validVariables = function (string $attribute, mixed $value, \Closure $fail) use ($variables): void {
            if (! is_string($value)) {
                return;
            }
            preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $value, $matches);
            if (array_diff($matches[1], $variables) !== []) {
                $fail('Use apenas as variáveis disponíveis neste modelo.');
            }
        };

        return [
            'title_template' => ['required', 'string', 'max:255', $validVariables],
            'in_app_template' => ['required', 'string', 'max:2000', $validVariables],
            'email_subject_template' => ['nullable', 'string', 'max:255', $validVariables],
            'email_template' => ['nullable', 'string', 'max:5000', $validVariables],
            'action_label_template' => ['nullable', 'string', 'max:100', $validVariables],
            'action_url_template' => ['nullable', 'string', Rule::in([$definition['action_url_template']])],
            'channels' => ['required', 'array', 'min:1', 'max:3'],
            'channels.*' => ['required', 'distinct', Rule::in(['database', 'broadcast', 'mail'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'enabled' => ['required', 'boolean'],
            'lab_id' => ['prohibited'],
            'key' => ['prohibited'],
            'updated_by_id' => ['prohibited'],
            'audience_permission' => ['prohibited'],
            'category' => ['prohibited'],
            'variables' => ['prohibited'],
            'name' => ['prohibited'],
        ];
    }
}
