<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view') || $user->projects()->exists();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->can('projects.view')) {
            return true;
        }
        return $project->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->can('projects.edit')) {
            return true;
        }
        $pivot = $project->users()->where('users.id', $user->id)->first();
        return $pivot && in_array($pivot->pivot->access_level, ['owner', 'admin'], true);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete');
    }
}
