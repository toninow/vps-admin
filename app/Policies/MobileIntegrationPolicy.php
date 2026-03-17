<?php

namespace App\Policies;

use App\Models\MobileIntegration;
use App\Models\User;

class MobileIntegrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('mobile_integrations.view');
    }

    public function view(User $user, MobileIntegration $mobileIntegration): bool
    {
        return $user->can('mobile_integrations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('mobile_integrations.edit');
    }

    public function update(User $user, MobileIntegration $mobileIntegration): bool
    {
        return $user->can('mobile_integrations.edit');
    }

    public function delete(User $user, MobileIntegration $mobileIntegration): bool
    {
        return $user->can('mobile_integrations.edit');
    }
}
