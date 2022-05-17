<?php

namespace App\Policies;

use App\Helpers\Policies\PolicyTraits;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class UserPolicy
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

    public function can_send_message(User $user, User $chat_user, Vendor $vendor, Request $request)
    {
        if($request->from == 'USER')
            return $user->id == $chat_user->id;
        else
            return $vendor->user_id == $user->id;
    }
}
