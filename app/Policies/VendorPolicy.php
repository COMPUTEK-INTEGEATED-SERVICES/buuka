<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;
    use PolicyTraits;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function interact(User $user, Vendor $vendor): bool
    {
        return $user->id == $vendor->user_id && $vendor->status == 1;
    }
}
