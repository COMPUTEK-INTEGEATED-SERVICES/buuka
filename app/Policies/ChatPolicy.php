<?php

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChatPolicy
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

    public function participate(User $user, Chat $chat): bool
    {
        return $user->id == $chat->user_1 || $user->id == $chat->user_2;
    }
}
