<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \App\Models\Message
     */
    public $message;
    public $decrypt;

    /**
     * Create a new event instance.
     * 
     * @param \App\Models\Message $message
     *
     * @return void
     */
    public function __construct(Message $message,$decrypt)
    {
        $this->message = $message;
        $this->message->body = $decrypt;
        $this->decrypt = $decrypt;
        
    }
    // Assuming you have a method in your event class to decrypt the message.
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $other_user = $this->message->conversation->participants()
            ->where('user_id', '<>', $this->message->user_id)
            ->first();
     // return new PresenceChannel('Messenger.' . $other_user->id);
       return new Channel('Messenger.' . $other_user->id);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }
}
