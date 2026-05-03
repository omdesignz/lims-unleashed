<?php

namespace App\Support;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PersonnelQualificationGate
{
    public function ensure(User $user, string $capability, ?int $departmentId = null): void
    {
        if ($this->allows($user, $capability, $departmentId)) {
            return;
        }

        throw new HttpException(403, 'O utilizador não possui qualificação ativa para executar esta etapa.');
    }

    public function allows(User $user, string $capability, ?int $departmentId = null): bool
    {
        if (! method_exists($user, 'hasActiveQualificationFor')) {
            return true;
        }

        return $user->hasActiveQualificationFor($capability, $departmentId);
    }
}
