<?php

namespace App\Actions;

use App\Http\Requests\MaintenanceCategoryRequest;
use App\Models\MaintenanceCategory;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveMaintenanceCategory
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param array<string, mixed> $data */
    public function execute(int $userId, int $labId, array $data, ?int $categoryId = null): MaintenanceCategory
    {
        return DB::transaction(function () use ($userId, $labId, $data, $categoryId): MaintenanceCategory {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = ($categoryId ? 'edit' : 'add').'_maintenance_categories';
            $this->access->operator($userId, $labId, $permission);
            $category = $categoryId
                ? MaintenanceCategory::where('lab_id', $labId)->lockForUpdate()->findOrFail($categoryId)
                : new MaintenanceCategory;
            $validated = Validator::make($data, MaintenanceCategoryRequest::categoryRules($categoryId))->validate();
            $category->fill($validated);
            if (! $category->exists) {
                $category->lab_id = $labId;
            }
            if (! $category->exists || $category->isDirty()) {
                abort_unless($category->save(), 409, 'Não foi possível guardar a categoria.');
            }
            $this->access->operator($userId, $labId, $permission);

            return $category;
        }, 3);
    }
}
