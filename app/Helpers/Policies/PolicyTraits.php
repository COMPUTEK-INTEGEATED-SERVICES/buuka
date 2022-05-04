<?php


namespace App\Helpers\Policies;


use App\Models\User;

trait PolicyTraits
{
    public function perform_admin_task(User $user): bool
    {
        //return $user->hasRole('admin');
        return true;
    }
}
