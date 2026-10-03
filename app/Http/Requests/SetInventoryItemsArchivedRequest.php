<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemArchiveValidation;
use Illuminate\Foundation\Http\FormRequest;

class SetInventoryItemsArchivedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ability = $this->routeIs('*.restore') ? 'restore' : 'delete';
        if ($this->routeIs('iitems.*', 'iequipments.*')) {
            return (bool) $this->user()?->can($ability.'_'.($this->routeIs('iitems.*') ? 'iitems' : 'iequipments'));
        }

        return app(InventoryCatalogueAccess::class)->any($this->user(), $ability);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,array<mixed>>
     */
    public function rules(): array
    {
        return app(InventoryItemArchiveValidation::class)->rules();
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('item') instanceof InventoryItem) {
            $this->merge(['recordIds' => [$this->route('item')->id]]);
        }
    }
}
