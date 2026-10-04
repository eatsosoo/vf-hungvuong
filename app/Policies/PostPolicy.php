<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Editor], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Post $post): bool
    {
        return $this->viewAny($user) && ($user->role !== UserRole::Editor ||
            ($post->user_id === $user->id && $post->status === PostStatus::Draft));
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->publish($user);
    }

    public function publish(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function preview(User $user, Post $post): bool
    {
        return $this->viewAny($user) && ($user->role !== UserRole::Editor || $post->user_id === $user->id);
    }
}
