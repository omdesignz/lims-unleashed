<?php

namespace App\Services;

use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class LaboratoryWorkflowMutationAccess
{
    public function __construct(private readonly LaboratoryWorkflowOwnership $ownership) {}

    public function operator(int $userId, int $labId, string $permission): User
    {
        $laboratory = VAPLab::query()->lockForUpdate()->find($labId);
        $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)
            ->lockForUpdate()->first();
        $operator = $this->ownership->eligibleUsers($labId)->lockForUpdate()->find($userId);

        if (! $membership || ! $laboratory || ! $operator?->can($permission)) {
            throw new AuthorizationException('Sem autorização actual para modificar os dados deste laboratório.');
        }

        return $operator;
    }
}
