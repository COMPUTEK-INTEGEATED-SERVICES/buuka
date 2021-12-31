<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function interact(User $user, Product $product, Service $services): bool
    {
        return $user->id == $services->user_id && $services->id == $product->service_id;
    }

    public function create(User $user, Service $services)
    {
        return $user->id == $services->vendor_id;
    }
}
