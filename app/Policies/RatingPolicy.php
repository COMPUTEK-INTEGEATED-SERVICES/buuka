<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\Book;
use App\Models\Rating;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatingPolicy
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

    public function interact(User $user, Rating $rating): bool
    {
        return $user->id == $rating->user_id ;
    }
}
