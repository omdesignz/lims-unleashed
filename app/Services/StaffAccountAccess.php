<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StaffAccountAccess
{
    public const PROFILE_FIELDS = ['name', 'email', 'username', 'gender', 'dob', 'id_number', 'primary_phone', 'secondary_phone'];

    public const GLOBAL_ACCESS_FIELDS = ['departments', 'roles', 'permissions'];

    public function isSystemAdministrator(User $actor): bool
    {
        return $actor->is_active && $actor->hasVerifiedEmail() && $actor->hasRole('admin', 'web')
            && ! request()->session()->has('impersonate');
    }

    public function authorizeSystem(User $actor, string $permission): void
    {
        $freshActor = User::query()->find($actor->id);
        if (! $freshActor || ! $this->isSystemAdministrator($freshActor) || ! $freshActor->can($permission)) {
            throw new AuthorizationException('Esta alteração global exige um administrador do sistema.');
        }
    }

    /** @param array<string,mixed> $data */
    public function authorizeUpdate(User $actor, int $targetId, array $data, bool $intendedEmailVerificationReset = false): void
    {
        if (request()->session()->has('impersonate')) {
            throw new AuthorizationException('Não é permitido alterar contas durante uma sessão de representação.');
        }
        $systemAdministrator = $this->isSystemAdministrator($actor)
            || ($intendedEmailVerificationReset && $actor->id === $targetId && $actor->is_active && $actor->hasRole('admin', 'web'));
        if (! $systemAdministrator && array_intersect(array_keys($data), self::GLOBAL_ACCESS_FIELDS) !== []) {
            throw new AuthorizationException('Os acessos globais são geridos pelo administrador do sistema.');
        }
        if (! $systemAdministrator && $actor->id !== $targetId && array_intersect(array_keys($data), self::PROFILE_FIELDS) !== []) {
            throw new AuthorizationException('Os dados pessoais são geridos pelo titular ou pelo administrador do sistema.');
        }
    }

    /** @return array<string,bool> */
    public function capabilities(User $actor, ?User $target = null): array
    {
        $systemAdministrator = $this->isSystemAdministrator($actor);
        $self = $target && $actor->is($target);

        return [
            'create' => $systemAdministrator && $actor->can('add_users'),
            'join' => ! request()->session()->has('impersonate') && $actor->can('add_users'),
            'removeMembership' => ! request()->session()->has('impersonate') && ! $self && $actor->can('delete_users'),
            'profile' => ! request()->session()->has('impersonate') && ($systemAdministrator || $self) && $actor->can('edit_users'),
            'departments' => $systemAdministrator && $actor->can('edit_users'),
            'roles' => $systemAdministrator && $actor->can('edit_roles'),
            'permissions' => $systemAdministrator && $actor->can('edit_permissions'),
            'qualifications' => ! request()->session()->has('impersonate') && $actor->can('edit_users'),
            'password' => $systemAdministrator && $actor->can('reset-password_users'),
            'delete' => $systemAdministrator && ! $self && $actor->can('delete_users'),
            'restore' => $systemAdministrator && ! $self && $actor->can('restore_users'),
            'status' => $systemAdministrator && ! $self && $actor->can('ban_users'),
            'impersonate' => $systemAdministrator && ! $self && $actor->can('impersonate_users'),
        ];
    }
}
