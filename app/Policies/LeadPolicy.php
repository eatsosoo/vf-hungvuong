<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Sales], true);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->viewAny($user) && ($user->role !== UserRole::Sales || $lead->assigned_to === $user->id);
    }

    public function assign(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }
}
