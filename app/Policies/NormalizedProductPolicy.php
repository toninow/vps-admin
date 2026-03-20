<?php

namespace App\Policies;

use App\Models\NormalizedProduct;
use App\Models\User;

class NormalizedProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, NormalizedProduct $normalizedProduct): bool
    {
        return $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.edit');
    }

    public function update(User $user, NormalizedProduct $normalizedProduct): bool
    {
        return $user->can('products.edit');
    }

    public function delete(User $user, NormalizedProduct $normalizedProduct): bool
    {
        return $user->can('products.edit');
    }
}
