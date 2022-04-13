<?php


namespace App\Http\Controllers\Action;


use App\Models\User;
use Illuminate\Support\Str;

class AuthenticationAction
{
    public function returnToken(User $user): string
    {
        return $user->createToken(Str::random(5))->accessToken;
    }
}
