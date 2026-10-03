<?php

namespace App\Http\Requests;

use App\Services\SampleLaboratoryAccess;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class InventoryPositionLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if($this->session()->has('impersonate'), 403);
        abort_unless($this->user()?->can($this->routeIs('inventory.restore') ? 'restore_inventory' : 'delete_inventory'), 403);
        app(SampleLaboratoryAccess::class)->activeLabId();

        return true;
    }

    /** @return array<string,array<mixed>> */
    public function rules(): array
    {
        return ['recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['bail', 'required', function (string $attribute, mixed $value, Closure $fail): void {
                if ((! is_int($value) && ! is_string($value)) || ! preg_match('/^[1-9][0-9]*$/', (string) $value)) {
                    $fail('Seleccione um identificador inteiro positivo.');
                }
            }, 'integer', 'min:1', 'max:'.PHP_INT_MAX, 'distinct']];
    }
}
