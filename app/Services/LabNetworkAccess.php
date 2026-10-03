<?php

namespace App\Services;

use App\Models\LabNetwork;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LabNetworkAccess
{
    /** @return Collection<int, VAPLab> */
    public function memberships(User $user): Collection
    {
        return VAPLab::query()->whereIn('id', DB::table('lab_user')->where('user_id', $user->id)->select('lab_id'))
            ->with('network')->orderBy('name')->get();
    }

    public function canViewNetwork(User $user, LabNetwork $network): bool
    {
        return $network->main_lab_id !== null && $network->labs()->whereKey($network->main_lab_id)->exists()
            && DB::table('lab_user')->where('user_id', $user->id)->where('lab_id', $network->main_lab_id)
                ->where('can_view_network', true)->exists();
    }

    public function canManageBranding(User $user, VAPLab $lab): bool
    {
        return ! $lab->trashed() && DB::table('lab_user')->where('user_id', $user->id)
            ->where('lab_id', $lab->id)->where('can_manage_branding', true)->exists();
    }

    public function visibleLabs(User $user, LabNetwork $network): Builder
    {
        $query = $network->labs();

        if (! $this->canViewNetwork($user, $network)) {
            $query->whereIn('labs.id', DB::table('lab_user')->where('user_id', $user->id)->select('lab_id'));
        }

        return $query->getQuery();
    }

    /** @return array{labs: array<int, array<string, mixed>>, active_lab: ?array<string, mixed>} */
    public function context(User $user, ?int $activeLabId): array
    {
        $labs = $this->memberships($user);
        $active = $labs->firstWhere('id', $activeLabId) ?? $labs->first();
        $serialize = fn (VAPLab $lab): array => [
            'id' => $lab->id,
            'name' => $lab->name,
            'network_id' => $lab->network_id,
            'network_name' => $lab->network?->name,
            'primary_color' => $lab->primary_color ?? $lab->network?->primary_color ?? '#0757b5',
            'inherited_color' => $lab->primary_color === null,
            'can_manage_branding' => $this->canManageBranding($user, $lab),
            'can_view_network' => $lab->network !== null && $this->canViewNetwork($user, $lab->network),
        ];

        return ['labs' => $labs->map($serialize)->all(), 'active_lab' => $active ? $serialize($active) : null];
    }
}
