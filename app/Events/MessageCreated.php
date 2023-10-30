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
    // $key = decrypt($message->conversation->key);
    // $encryptedMessage = $message->body;

    // // Decode the base64 encoded message
    // $messageWithIv = base64_decode($encryptedMessage);

    // // Extract the IV and encrypted message
    // $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    // $iv = substr($messageWithIv, 0, $ivLength);
    // $encryptedMessage = substr($messageWithIv, $ivLength);

    // // Decrypt the message
    // $decryptedMessage = openssl_decrypt($encryptedMessage, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    // // Check for decryption errors
    // if ($decryptedMessage === false) {
    //     // Handle decryption error (you might want to log this or throw an exception)
    //     throw new \Exception("Decryption error: " . openssl_error_string());
    // }
    // $this->message->body = $decryptedMessage;   
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
      //return new PresenceChannel('Messenger.' . $other_user->id);
      return new Channel('Messenger.' . $other_user->id);
    }
    public function broadcastAs()
    {
        return 'new-message';
    }
}
