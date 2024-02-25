<?php

namespace App\Policies\Api\v1;

use App\Enums\ConversationActionType;
use App\Enums\ConversationRoleType;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ConversationPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the user can create new messages in given conversation.
     *
     * @param  User $user
     * @param  Conversation $conversation
     * @return bool
     */
    public function create(User $user, Conversation $conversation)
    {
        return $conversation->users->contains($user);
    }

    /**
     * Determine if the user can view conversation content.
     *
     * @param  User $user
     * @param  Conversation $conversation
     * @return bool
     */
    public function view(User $user, Conversation $conversation)
    {
        // Check if user is participant in the conversation
        return $conversation->users->contains($user);
    }

    /**
     * Determine if the user can update conversation.
     *
     * @param  User $user
     * @param  Conversation $conversation
     * @param  string $actions
     * @param  int $acted_user
     * @return bool
     */
    public function update(User $user, Conversation $conversation, string $action, int $acted_user = null)
    {

        // Check if user has admin role for name/icon updates
        if (in_array($action, [ConversationActionType::UPDATE_NAME, ConversationActionType::UPDATE_ICON])) {

            return $conversation->users->where('id', $user->id)
                ->where('role', ConversationRoleType::ADMIN)
                ->exists();
        }

        // Check if user has admin role for user management based on action
        if ($action === ConversationActionType::ADD_USER) {

            return $conversation->users->where('id', $user->id)
                ->whereIn('role', [ConversationRoleType::ADMIN, ConversationRoleType::MODERATOR])
                ->exists();

        } elseif ($action === ConversationActionType::REMOVE_USER) {
            $conversation_user = $conversation->users->where('id', $user->id);
            $acted_user = $conversation->users->where('id', $acted_user);
            switch($conversation_user->role){
                case ConversationRoleType::ADMIN:
                    // Admin cannot remove himself from group
                    if($acted_user->id == $user->id){
                        return false;
                    }
                    return true;
                case ConversationRoleType::MODERATOR:
                    if($acted_user->role == ConversationRoleType::ADMIN){
                        return false;
                    }elseif($acted_user->role == ConversationRoleType::MODERATOR){
                        if($acted_user->id != $user->id){
                            return false;
                        }
                    }
                    return true;
                case ConversationRoleType::PARTICIPANT:
                    if($acted_user->id == $user->id){
                        return false;
                    }
                    return true;


            }
        } else {
            // Participants can't add anyone
            return false; // Deny unauthorized access
        }

        return true;
    }
}
