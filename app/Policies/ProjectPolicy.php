<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->canBeAccessedBy($user);
    }

    public function create(User $user): bool
    {
        $workspace = Filament::getTenant();

        return $workspace instanceof Workspace && $workspace->canBeManagedBy($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->canBeManagedBy($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->canBeManagedBy($user);
    }

    public function restore(User $user, Project $project): bool
    {
        return $project->canBeManagedBy($user);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $project->canBeManagedBy($user);
    }
}
