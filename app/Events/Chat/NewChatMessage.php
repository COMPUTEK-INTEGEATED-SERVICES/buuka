<?php

namespace App\Events\Chat;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Chat
     */
    private $chat;
    /**
     * @var User
     */
    private $user;
    /**
     * @var User
     */
    private $to_user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Chat $chat, User $user, User $to_user)
    {
        $this->chat = $chat;
        $this->user = $user;
        $this->to_user = $to_user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("chat-room-{$this->to_user->id}");
    }

    public function broadcastWith()
    {
        return ['message' => $this->chat, 'user' => $this->user];
    }
}
