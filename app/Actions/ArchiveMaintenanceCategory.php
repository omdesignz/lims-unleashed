<?php

namespace App\Actions;

use App\Models\MaintenanceCategory;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;

class ArchiveMaintenanceCategory
{
    public function __construct(private readonly LaboratoryWorkflowMutationAccess $access) {}

    public function execute(int $userId, int $labId, int $categoryId, bool $restore = false): void
    {
        DB::transaction(function () use ($userId, $labId, $categoryId, $restore): void {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $permission = ($restore ? 'restore' : 'delete').'_maintenance_categories';
            $this->access->operator($userId, $labId, $permission);
            $category = MaintenanceCategory::withTrashed()->where('lab_id', $labId)->lockForUpdate()->findOrFail($categoryId);
            if ($category->trashed() === $restore) {
                abort_unless($restore ? $category->restore() : $category->delete(), 409, 'Não foi possível actualizar o arquivo.');
            }
            $this->access->operator($userId, $labId, $permission);
        }, 3);
    }
}
