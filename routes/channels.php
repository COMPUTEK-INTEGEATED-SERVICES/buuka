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
Broadcast::channel('chat-room-{receiver}-{sender}', function ($user, $receiver, $sender) {
    //$chat = \App\Models\Chat::senderReceiver($sender, $receiver);
    return /*!$chat->isEmpty() && */($user->id == $sender || $user->id == $receiver);
});

Broadcast::channel('user-notify-{userID}', function ($user, $userID) {
    return $user->id == $userID;
});

Broadcast::channel('payment-event-{reference}', function (){
    return true;
});
