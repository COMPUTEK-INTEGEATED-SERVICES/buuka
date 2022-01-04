<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/*Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});*/
Broadcast::channel('chat-room-{chat}', function ($user, $chat) {
    $chat = \App\Models\Chat::find($chat);
    return $user->id == $chat->user_1 || $user->id == $chat->user_2;
    // return true;
});

Broadcast::channel('user-notify-{userID}', function ($user, $userID) {
    return true;
});
