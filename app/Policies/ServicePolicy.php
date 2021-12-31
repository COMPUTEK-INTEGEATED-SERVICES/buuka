<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
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

    public function interact(User $user, Service $service): bool
    {
        return $user->id === $service->vendor_id;
    }
}
