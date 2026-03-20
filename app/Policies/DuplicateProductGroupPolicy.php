<?php

namespace App\Policies;

use App\Models\DuplicateProductGroup;
use App\Models\User;

class DuplicateProductGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('duplicates.view');
    }

    public function view(User $user, DuplicateProductGroup $duplicateProductGroup): bool
    {
        return $user->can('duplicates.view');
    }

    public function update(User $user, DuplicateProductGroup $duplicateProductGroup): bool
    {
        return $user->can('duplicates.merge');
    }
}
