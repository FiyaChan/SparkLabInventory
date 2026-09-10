<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    // Storefront browsing — anyone authenticated (or even guests, if you
    // choose to allow guest browsing) can view an active product.
    public function view(?User $user, Product $product): bool
    {
        return $product->is_active || ($user && $user->can('product.view'));
    }

    public function create(User $user): bool
    {
        return $user->can('product.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('product.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('product.delete');
    }
}
