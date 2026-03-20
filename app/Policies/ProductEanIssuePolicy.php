<?php

namespace App\Policies;

use App\Models\ProductEanIssue;
use App\Models\User;

class ProductEanIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ean.view');
    }

    public function view(User $user, ProductEanIssue $productEanIssue): bool
    {
        return $user->can('ean.view');
    }

    public function update(User $user, ProductEanIssue $productEanIssue): bool
    {
        return $user->can('ean.resolve');
    }
}
