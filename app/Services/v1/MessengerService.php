<?php

namespace App\Services\v1;

use App\Enums\ConversationType;
use App\Events\Chat\MessageRead;
use App\Events\Chat\SendMessage;
use App\Http\Resources\v1\MessageResource;
use Exception;
use App\Models\Message;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Responses\v1\ApiResponse;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class MessengerService
{

    /**
     * Get max file's upload size in MB.
     *
     * @return int
     */
    public function getMaxUploadSize()
    {
        return config('messenger.attachments.max_upload_size') * 1048576;
    }


    /**
     * This method returns the allowed image extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedImages()
    {
        return config('messenger.attachments.allowed_images');
    }

    /**
     * This method returns the allowed file extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedFiles()
    {
        return config('messenger.attachments.allowed_files');
    }

    /**
     * Returns an array contains messenger's colors
     *
     * @return array
     */
    public function getMessengerColors()
    {
        return config('messenger.colors');
    }

    /**
     * Returns a fallback primary color.
     *
     * @return array
     */
    public function getFallbackColor()
    {
        $colors = $this->getMessengerColors();
        return count($colors) > 0 ? $colors[0] : '#000000';
    }


    /**
     * Fetch & parse message and return the message card
     * view as a response.
     *
     * @param Message $prefetchedMessage
     * @param int $id
     * @return array
     */
    public function parseMessage($prefetchedMessage = null, $id = null)
    {
        $msg = null;
        $attachment = null;
        $attachment_type = null;
        $attachment_title = null;
        if (!!$prefetchedMessage) {
            $msg = $prefetchedMessage;
        } else {
            $msg = Message::where('id', $id)->first();
            if (!$msg) {
                return [];
            }
        }
        if (isset($msg->attachment)) {
            $attachmentOBJ = json_decode($msg->attachment);
            $attachment = $attachmentOBJ->new_name;
            $attachment_title = htmlentities(trim($attachmentOBJ->old_name), ENT_QUOTES, 'UTF-8');
            $ext = pathinfo($attachment, PATHINFO_EXTENSION);
            $attachment_type = in_array($ext, $this->getAllowedImages()) ? 'image' : 'file';
        }
        return [
            'id' => $msg->id,
            'senderId' => $msg->sender_id,
            'toId' => $msg->conversation,
            'message' => $msg->content,
            'attachment' => (object) [
                'file' => $attachment,
                'title' => $attachment_title,
                'type' => $attachment_type
            ],
            'timeAgo' => $msg->created_at->diffForHumans(),
            'createdAt' => $msg->created_at->toIso8601String(),
            'isSender' => ($msg->from_id == Auth::user()->id),
            'seen' => $msg->seen,
        ];
    }


    /**
     * Save files
     * @param $file
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function handleFiles($file)
    {
        $attachment = null;
        $attachment_title = null;

        // allowed extensions
        $allowed_images = $this->getAllowedImages();
        $allowed_files  = $this->getAllowedFiles();
        $allowed        = array_merge($allowed_images, $allowed_files);

        // check file size
        if ($file->getSize() < $this->getMaxUploadSize()) {
            if (in_array(strtolower($file->extension()), $allowed)) {
                // get attachment name
                $attachment_title = $file->getClientOriginalName();
                // upload attachment and store the new name
                $attachment = Str::uuid() . "." . $file->extension();
                $file->storeAs(config('messenger.attachments.folder'), $attachment, config('messenger.storage_disk_name'));
            } else {
                return ApiResponse::error("File extension not allowed!")->respond();
            }
        } else {
            return ApiResponse::error("File size you are trying to upload is too large!")->respond();
        }

        return [
            'attachment' => $attachment,
            'attachment_title' => $attachment_title,
        ];
    }

    /**
     * Return a message card with the given data.
     *
     * @param Message $data
     * @param boolean $isSender
     * @return string
     */
    public function messageCard($data, $renderDefaultCard = false)
    {
        if (!$data) {
            return '';
        }
        if ($renderDefaultCard) {
            $data['isSender'] =  false;
        }
        return view('messenger::layouts.messageCard', $data)->render();
    }

    /**
     * Default fetch messages query between a Sender and Receiver.
     *
     * @param int $user_id
     * @return Message|\Illuminate\Database\Eloquent\Builder
     */
    public function fetchMessagesQuery($conversation_id)
    {
        return Conversation::findOrFail($conversation_id)->messages();
    }

    /**
     * create a new message to database
     *
     * @param Conversation $conversation
     * @param array $data
     * @return Message
     */
    public function newMessage(Conversation $conversation, array $data)
    {
        $message = new Message();
        $message->sender_id = $data['sender_id'];
        $message->conversation_id = $data['conversation_id'];
        $message->content = $data['content'];
        $message->attachment = $data['attachment'];
        $message->save();

        // send to user using websocket
        foreach ($conversation->users()->where('user_id', '!=', Auth::user()->id)->get() as $user) {
            broadcast(new SendMessage(Auth::user()->id, $user->id, new MessageResource($message)));
        }

        return $message;
    }

    /**
     * Make messages between the sender [Auth user] and
     * the receiver [User id] as seen.
     *
     * @param Conversation $conversation
     * @return bool
     */
    public function makeSeen(Conversation $conversation)
    {
        // Get the current user
        $currentUser = Auth::user();

        if ($conversation->type == ConversationType::DIRECT) {
            Message::where('sender_id', '!=', $currentUser->id)
                ->where('conversation_id', $conversation->id)
                ->whereNull('seen_by')
                ->update(['seen_by' => json_encode($currentUser->id)]);



            broadcast(new MessageRead(
                $conversation->id,
                $conversation->users->where('id', '!=', $currentUser->id)->first()->id
            ));

        } else {
            // Get all unread messages for the conversation (excluding the current message)
            $unreadMessages = Message::where('sender_id', '!=', $currentUser->id)
                ->where('conversation_id', $conversation->id)
                ->where('seen_by', 'NOT LIKE', '%"' . $currentUser->id . '"%') // Check if current user ID is not in seen_by
                ->get();

            // Mark all unread messages as seen for the current user
            foreach ($unreadMessages as $message) {
                // Decode seen_by JSON and add current user ID (if not already present)
                $seenBy = json_decode($message->seen_by, true) ?? [];

                if (!in_array($currentUser->id, $seenBy)) {
                    $seenBy[] = $currentUser->id;
                }

                // Update the message with the updated seen_by list
                $message->update(['seen_by' => json_encode($seenBy)]);
            }
        }

        return 1;
    }

    /**
     * Get last message for a specific user
     *
     * @param int $user_id
     * @return Message|Collection|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getLastMessageQuery($conversation_id)
    {
        return $this->fetchMessagesQuery($conversation_id)->latest()->first();
    }

    /**
     * Count Unseen messages for a conversation for Auth::user()
     *
     * @param Conversation $conversation
     * @return int
     */
    public function countUnseenMessages(Conversation $conversation)
    {
        return Message::where('sender_id', '!=', Auth::user()->id)
            ->where('conversation_id', $conversation->id)
            ->where('seen_by', 'NOT LIKE', '%"' . Auth::user()->id . '"%') // Check if current user ID is not in seen_by
            ->count();
    }



    // /**
    //  * Check if a user in the favorite list
    //  *
    //  * @param int $user_id
    //  * @return boolean
    //  */
    // public function inFavorite($user_id)
    // {
    //     return Favorite::where('user_id', Auth::user()->id)
    //                     ->where('favorite_id', $user_id)->count() > 0
    //                     ? true : false;
    // }

    // /**
    //  * Make user in favorite list
    //  *
    //  * @param int $user_id
    //  * @param int $star
    //  * @return boolean
    //  */
    // public function makeInFavorite($user_id, $action)
    // {
    //     if ($action > 0) {
    //         // Star
    //         $star = new Favorite();
    //         $star->user_id = Auth::user()->id;
    //         $star->favorite_id = $user_id;
    //         $star->save();
    //         return $star ? true : false;
    //     } else {
    //         // UnStar
    //         $star = Favorite::where('user_id', Auth::user()->id)->where('favorite_id', $user_id)->delete();
    //         return $star ? true : false;
    //     }
    // }

    /**
     * Get shared photos of the conversation
     *
     * @param int $user_id
     * @return array
     */
    public function getSharedPhotos($conversation_id)
    {
        $images = array(); // Default
        // Get messages
        $msgs = $this->fetchMessagesQuery($conversation_id)->orderBy('created_at', 'DESC');
        if ($msgs->count() > 0) {
            foreach ($msgs->get() as $msg) {
                // If message has attachment
                if ($msg->attachment) {
                    $attachment = json_decode($msg->attachment);
                    // determine the type of the attachment
                    in_array(pathinfo($attachment->new_name, PATHINFO_EXTENSION), $this->getAllowedImages())
                        ? array_push($images, $attachment->new_name) : '';
                }
            }
        }
        return $images;
    }

    /**
     * Delete Conversation
     *
     * @param int $user_id
     * @return boolean
     */
    public function deleteConversation($conversation_id)
    {
        try {
            foreach ($this->fetchMessagesQuery($conversation_id)->get() as $msg) {
                // delete file attached if exist
                if (isset($msg->attachment)) {
                    $path = config('messenger.attachments.folder') . '/' . json_decode($msg->attachment)->new_name;
                    if (self::storage()->exists($path)) {
                        self::storage()->delete($path);
                    }
                }
                // delete from database
                $msg->delete();
            }
            return 1;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Delete message by ID
     *
     * @param int $id
     * @return ApiResponse
     */
    public function deleteMessage($id)
    {
        try {
            $msg = Message::where('sender_id', auth()->id())->where('id', $id)->firstOrFail();
            if (isset($msg->attachment)) {
                $path = config('messenger.attachments.folder') . '/' . json_decode($msg->attachment)->new_name;
                if (self::storage()->exists($path)) {
                    self::storage()->delete($path);
                }
            }
            $msg->delete();
            return ApiResponse::success([], 'Message Deleted');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Return a storage instance with disk name specified in the config.
     *
     */
    public function storage()
    {
        return Storage::disk(config('messenger.storage_disk_name'));
    }
}
