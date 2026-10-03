<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->routeIs('inventory.create')) {
            return (bool) $user?->can('add_inventory');
        }
        if ($this->routeIs('inventory.edit')) {
            return (bool) $user?->can('edit_inventory');
        }
        $creating = $this->boolean('create');
        $editing = $this->has('edit');
        abort_if($creating && ! $user?->can('add_inventory'), 403);
        abort_if($editing && ! $user?->can('edit_inventory'), 403);

        return (bool) $user?->can('view_inventory')
            || ($creating && $user?->can('add_inventory'))
            || ($editing && $user?->can('edit_inventory'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'create' => ['sometimes', 'boolean'],
            'edit' => ['sometimes', 'bail', 'required', function (string $attribute, mixed $value, Closure $fail): void {
                if ((! is_int($value) && ! is_string($value)) || preg_match('/^[1-9][0-9]*$/', (string) $value) !== 1) {
                    $fail('A posição deve ter um identificador inteiro positivo.');
                }
            }, 'integer', 'min:1', 'max:'.PHP_INT_MAX, 'prohibits:create'],
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', Rule::in(['trashed'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return match (true) {
            $this->routeIs('inventory.create') => ['create' => true],
            $this->routeIs('inventory.edit') => ['edit' => $this->route('inventory')],
            default => parent::validationData(),
        };
    }
}
