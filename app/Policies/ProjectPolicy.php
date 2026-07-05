<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use App\Support\WorkspaceAccess;

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

        $accountWorkspaces = app(AccountWorkspaceService::class);
        $workspace = $accountWorkspaces->ensureFor($user);
        $projectLimit = WorkspaceAccess::limit($workspace, 'projects');
        $ownedProjectCount = $accountWorkspaces->ownedProjectCount($user);

        return $workspace instanceof Workspace
            && $workspace->canCreateProjectsBy($user)
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
