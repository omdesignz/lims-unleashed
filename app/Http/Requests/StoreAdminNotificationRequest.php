<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        return app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)
            ->whereKey($this->user()?->id)->first()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->rulesForLaboratory(app(SampleLaboratoryAccess::class)->activeLabId());
    }

    /** @return array<string, mixed> */
    public function rulesForLaboratory(int $labId): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', Rule::in(['info', 'success', 'warning', 'error', 'alert'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'recipient_type' => ['required', Rule::in(['specific', 'group', 'all'])],
            'recipients' => ['exclude_unless:recipient_type,specific', 'required', 'array', 'min:1'],
            'recipients.*' => ['bail', 'required', 'integer', 'distinct', Rule::exists('users', 'id')->where(function (Builder $query) use ($labId): void {
                $query->whereIn('id', app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->select('users.id')->toBase());
            })],
            'group' => ['exclude_unless:recipient_type,group', 'required', Rule::in(['all', 'active', 'new', 'admins'])],
            'schedule_send' => ['nullable', 'boolean'],
            'scheduled_at' => ['prohibited'],
            'expires_at' => ['prohibited'],
            'lab_id' => ['prohibited'],
            'sender_id' => ['prohibited'],
            'recipient_count' => ['prohibited'],
        ];
    }
}
