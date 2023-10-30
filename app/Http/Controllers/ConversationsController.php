<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Recipient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CryptionController;

class ConversationsController extends CryptionController
{
    public function index()
    {
        $user = Auth::user();
        $conversations = $user->conversations()->with([
            'lastMessage',
            'participants'
              => function($builder) use ($user) {
                  $builder->where('user_id','<>', $user->id);
              },
            ])
            ->withCount([
                'recipients as new_messages' => function($builder) use ($user) {
                    $builder->where('recipients.user_id', '=', $user->id)
                        ->whereNull('read_at');
                }
            ])->paginate();
        //    ->paginate();
        
        // Decrypt the last message without losing the pagination structure
        
        $conversations->getCollection()->transform(function ($conversation) 
        {
           
            // Decrypt the last message
            if ($conversation->lastMessage && $conversation->key) {
                $key = decrypt($conversation->key); // Decrypt the key
             
                $conversation->lastMessage->body = $this->decryptMessage($conversation->lastMessage->body, $key);
                
            }
            return $conversation;
    });
         return $conversations;
        // return [
        //     'myId' => $user->id,
        //     'conversations' => $conversations
        // ];
      }
    public function show($id)
    {
        $user = Auth::user();
        return $user->conversations()->with([
            'lastMessage',
            'participants' => function($builder) use ($user) {
                $builder->where('user_id', '<>', $user->id);
            },])
            ->withCount([
                'recipients as new_messages' => function($builder) use ($user) {
                    $builder->where('recipients.user_id', '=', $user->id)
                        ->whereNull('read_at');
                }
            ])
            ->findOrFail($id);
    }

    public function addParticipant(Request $request, Conversation $conversation)
    {
        $request->validate([
            'user_id' => ['required', 'int', 'exists:users,id'],
        ]);

        $conversation->participants()->attach($request->post('user_id'), [
            'joined_at' => Carbon::now(),
        ]);
    }

    public function removeParticipant(Request $request, Conversation $conversation)
    {
        $request->validate([
            'user_id' => ['required', 'int', 'exists:users,id'],
        ]);

        $conversation->participants()->detach($request->post('user_id'));
    }

    public function markAsRead($id)
    {
        Recipient::where('user_id', '=', Auth::id())
            ->whereNull('read_at')
            ->whereRaw('message_id IN (
                SELECT id FROM messages WHERE conversation_id = ?
            )', [$id])
            ->update([
                'read_at' => Carbon::now(),
            ]);

        return [
            'message' => 'Messages marked as read',
        ];
    }

    public function destroy($id)
    {
        Recipient::where('user_id', '=', Auth::id())
            ->whereRaw('message_id IN (
                SELECT id FROM messages WHERE conversation_id = ?
            )', [$id])
            ->delete();

        return [
            'message' => 'Conversation deleted',
        ];
    }
}
