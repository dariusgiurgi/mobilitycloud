<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

class AccountWorkspaceService
{
    public function ensureFor(User $user): Workspace
    {
        $workspace = $user->workspaces()
            ->wherePivot('role', 'owner')
            ->orderBy('workspaces.created_at')
            ->first();

        if ($workspace) {
            $this->setCurrent($user, $workspace);

            return $workspace;
        }

        $workspace = Workspace::create([
            'name' => $this->defaultName($user),
            'plan' => 'free',
            'subscription_status' => 'active',
        ]);

        $workspace->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->setCurrent($user, $workspace);

        return $workspace;
    }

    public function preferredFor(User $user): Workspace
    {
        return $this->ensureFor($user);
    }

    public function ownedProjectCount(User $user): int
    {
        return Project::query()
            ->whereHas('workspace.users', fn ($members) => $members
                ->whereKey($user->id)
                ->where('workspace_user.role', 'owner'))
            ->count();
    }

    private function setCurrent(User $user, Workspace $workspace): void
    {
        if ((int) $user->current_workspace_id === (int) $workspace->id) {
            return;
        }

        $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        $user->setRelation('currentWorkspace', $workspace);
    }

    private function defaultName(User $user): string
    {
        $name = trim((string) $user->name);
        $name = $name !== '' ? $name : Str::before((string) $user->email, '@');

        return trim($name) !== '' ? $name.' projects' : 'My projects';
    }
}
