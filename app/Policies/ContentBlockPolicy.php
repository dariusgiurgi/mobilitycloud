<?php

namespace App\Policies;

use App\Models\ContentBlock;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;

class ContentBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentBlock $block): bool
    {
        return $user->workspaces()->whereKey($block->workspace_id)->exists();
    }

    public function create(User $user): bool
    {
        $workspace = Filament::getTenant();

        return $workspace instanceof Workspace && $workspace->canBeManagedBy($user);
    }

    public function update(User $user, ContentBlock $block): bool
    {
        return $block->workspace?->canBeManagedBy($user) ?? false;
    }

    public function delete(User $user, ContentBlock $block): bool
    {
        return $this->update($user, $block);
    }
}
