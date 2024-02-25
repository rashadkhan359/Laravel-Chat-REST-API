<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation_id;
    public $toUserId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($conversation_id, $toUserId)
    {
        $this->conversation_id = $conversation_id;
        $this->toUserId = $toUserId;
    }

    public function broadcastWith(){
        return [
            'conversation_id' => $this->conversation_id,
            'to_user_id' => $this->toUserId,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->toUserId);
    }
    
    public function broadcastAs(){
        return 'message-read';
    }
}

