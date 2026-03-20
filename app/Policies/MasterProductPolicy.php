<?php

namespace App\Policies;

use App\Models\MasterProduct;
use App\Models\User;

class MasterProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master_products.view');
    }

    public function view(User $user, MasterProduct $masterProduct): bool
    {
        return $user->can('master_products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master_products.create');
    }

    public function update(User $user, MasterProduct $masterProduct): bool
    {
        return $user->can('master_products.edit');
    }

    public function delete(User $user, MasterProduct $masterProduct): bool
    {
        return $user->can('master_products.delete');
    }

    public function approve(User $user, MasterProduct $masterProduct): bool
    {
        return $user->can('master_products.approve');
    }
}
