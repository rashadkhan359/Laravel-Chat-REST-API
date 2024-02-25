<?php

namespace App\Policies\Api\v1;

use App\Enums\ConversationRoleType;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    // /**
    //  * Determine if the user can create new messages.
    //  *
    //  * @param  User  $user
    //  * @param  Conversation  $conversation
    //  * @return bool
    //  */
    // public function create(User $user, Conversation $conversation)
    // {
    //     return $conversation->users->contains($user);
    // }

    /**
     * Determine if the user can view messages.
     *
     * @param  User  $user
     * @param  Message  $message
     * @return bool
     */
    public function view(User $user, Message $message)
    {
        // Check if user is participant in the conversation
        return $message->conversation->users->contains($user);
    }

    /**
     * Determine if the user can update a message.
     *
     * @param  User  $user
     * @param  Message  $message
     * @return bool
     */
    public function update(User $user, Message $message)
    {
        // Only allow updating own messages
        return $message->sender_id === $user->id;
    }

    /**
     * Determine if the user can delete a message.
     *
     * @param  User  $user
     * @param  Message  $message
     * @return bool
     */
    public function delete(User $user, Message $message)
    {
        if ($message->conversation->users->contains($user)){
            // Admins and moderators can delete any message
            $conversationUser = $message->conversation->users->where('user_id', $user->id);

            if($conversationUser->role == ConversationRoleType::ADMIN || $conversationUser->role == ConversationRoleType::MODERATOR){
                return true;
            }

            // Participants can delete their own messages
            return $message->sender_id === $user->id;
        }

        return false;
    }

    // ... other methods for additional actions (search, report, mark as read, etc.)

}
