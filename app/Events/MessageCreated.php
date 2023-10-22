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
    /**
     * Create a new event instance.
     * 
     * @param \App\Models\Message $message
     *
     * @return void
     */
    public function __construct(Message $message)
    {
          $this->message = $message;
        //   $key = decrypt($message->conversation->key);
        //   $encryptedMessage=$message->body;
        //   $key = str_pad($key, 32, "\0");
        //   $encryptedMessage = base64_decode($encryptedMessage);
        //   $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        //   $iv = substr($encryptedMessage, 0, $ivLength);
        //   $encryptedMessage = substr($encryptedMessage, $ivLength);
        //   $this->message->body= openssl_decrypt($encryptedMessage, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
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
