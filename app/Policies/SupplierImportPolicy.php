<?php

namespace App\Policies;

use App\Models\SupplierImport;
use App\Models\User;

class SupplierImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('imports.view');
    }

    public function view(User $user, SupplierImport $supplierImport): bool
    {
        return $user->can('imports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('imports.create');
    }

    public function update(User $user, SupplierImport $supplierImport): bool
    {
        return $user->can('imports.process');
    }

    public function delete(User $user, SupplierImport $supplierImport): bool
    {
        return $user->can('imports.process');
    }
}
