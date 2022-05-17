<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Vendor;
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

    public function interact(User $user, Product $product, Service $services, Vendor $vendor): bool
    {
        return $user->id == $vendor->user_id && $vendor->id == $services->vendor_id && $services->id == $product->service_id;
    }

    public function create(User $user, Service $services)
    {
        return $user->id == $services->vendor_id;
    }
}
