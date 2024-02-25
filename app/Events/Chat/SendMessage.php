<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fromUser;
    public $messageData;
    public $toUser;

    /**
     * Create a new event instance.
     *
     * @return void
    */

    public function __construct($fromUser, $toUser, $messageData)
    {
        $this->fromUser = $fromUser;
        $this->messageData = $messageData;
        $this->toUser = $toUser;
    }

    public function broadcastWith(){
        return [
            'fromUserId' => $this->fromUser,
            'toUserId' => $this->toUser,
            'data' => $this->messageData,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
    */

    public function broadcastOn()
    {
        //dd($this->user);

        return new PrivateChannel('chat.' . $this->toUser);
    }

    public function broadcastAs(){
        return 'message-sent';
    }
}
