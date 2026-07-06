<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Support\AccountAccess;

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
        if ($user->isPlatformAdmin()) {
            return false;
        }

        $projectLimit = AccountAccess::limit($user, 'projects');
        $ownedProjectCount = $user->ownedProjects()->count();

        return AccountAccess::isSubscriptionActive($user)
            && ! AccountAccess::isReadOnly($user)
            && ($projectLimit === null || $projectLimit === 0 || $ownedProjectCount < $projectLimit);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->canBeManagedBy($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->canManageLifecycleBy($user);
    }

    public function restore(User $user, Project $project): bool
    {
        return $project->canManageLifecycleBy($user);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $project->canManageLifecycleBy($user);
    }
}
