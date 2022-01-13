<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
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

    public function interact(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
