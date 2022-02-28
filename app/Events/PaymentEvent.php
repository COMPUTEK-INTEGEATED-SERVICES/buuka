<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $reference;
    /**
     * @var mixed|string
     */
    private $status;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($reference, $status = 'success')
    {
        $this->reference = $reference;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('payment-event-'.$this->reference);
    }

    public function broadcastWith()
    {
        return ['status' => $this->status];
    }
}
