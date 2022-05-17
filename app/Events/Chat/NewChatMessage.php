<?php

namespace App\Events\Chat;

use App\Models\Chat;
use App\Models\User;
use App\Models\Vendor;
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
     * @var Vendor
     */
    private $vendor;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Chat $chat, User $user, Vendor $vendor)
    {
        $this->chat = $chat;
        $this->user = $user;
        $this->vendor = $vendor;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("chat-room-{$this->user->id}-{$this->vendor->id}");
    }

    public function broadcastWith()
    {
        return ['message' => $this->chat];
    }
}
