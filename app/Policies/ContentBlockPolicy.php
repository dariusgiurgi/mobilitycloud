<?php

namespace App\Policies;

use App\Models\ContentBlock;
use App\Models\User;

class ContentBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentBlock $block): bool
    {
        return (int) $block->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContentBlock $block): bool
    {
        return (int) $block->owner_id === (int) $user->id;
    }

    public function delete(User $user, ContentBlock $block): bool
    {
        return $this->update($user, $block);
    }
}
