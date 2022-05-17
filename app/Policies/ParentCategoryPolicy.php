<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParentCategoryPolicy
{
    use HandlesAuthorization, PolicyTraits;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
