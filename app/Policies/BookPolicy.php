<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\Book;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookPolicy
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

    public function participate(User $user, Book $book, Vendor $vendor): bool
    {
        return $user->id == $book->user_id || $user->id == $vendor->user_id;
    }

    public function accept_proposal(User $user, Book $book)
    {
        return $book->type === 'custom' && ($book->proposed_by == 'vendor'? $user->id != $book->vendor->user_id:$user->id == $book->vendor->user_id);
    }
}
