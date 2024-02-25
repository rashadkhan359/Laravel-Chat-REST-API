<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\MessageStoreRequest;
use App\Http\Requests\Api\v1\UpdateMessageRequest;
use App\Http\Resources\v1\MessageCollection;
use App\Http\Resources\v1\MessageResource;
use App\Http\Responses\v1\ApiResponse;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\v1\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class MessageController extends Controller
{
    public $messengerService;
    public function __construct(MessengerService $messengerService)
    {
        $this->messengerService = $messengerService;
    }

    public function store(Conversation $conversation, MessageStoreRequest $request)
    {

        // Check access based on conversation type and role
        $this->authorize('create', [$conversation]);

        $response = [
            'attachment' => null,
            'attachment_title' => null
        ];

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            $response = $this->messengerService->handleFiles($request->file('file'));
        }

        if ($response instanceof JsonResponse) {
            return $response;
        } else {
            $message = $this->messengerService->newMessage($conversation, [
                'sender_id' => Auth::user()->id,
                'conversation_id' => $conversation->id,
                'content' => htmlentities(trim($request->message), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($response['attachment']) ? json_encode((object)[
                    'new_name' => $response['attachment'],
                    'old_name' => htmlentities(trim($response['attachment_title']), ENT_QUOTES, 'UTF-8'),
                ]) : null,
            ]);
        }

        return ApiResponse::success(new MessageResource($message), 'Message sent')->respond();
    }

    public function show(Conversation $conversation, Request $request)
    {
        // Check access based on conversation type and role
        $this->authorize('view', [$conversation]);

        $lastMessageId = $request->input('lastMessageId');

        $query = $conversation->messages()
            ->latest();

        if ($lastMessageId) {
            $query->where('id', '<', $lastMessageId);
        }

        $perPage = $request->get('perPage', 10); // Number of messages per page
        $messages = $query->paginate($perPage); // Pagination

        if(!$lastMessageId){
            // only broadcast on initial load
            $this->messengerService->makeSeen($conversation);
        }

        return ApiResponse::success(new MessageCollection($messages))->respond();
    }

    public function update(Message $message, UpdateMessageRequest $request)
    {
        // Check authorization based on message ownership and role
        $this->authorize('update', $message);

        $message->update($request->validated());

        return ApiResponse::success([$message])->respond();
    }

    public function delete(Conversation $conversation, Message $message)
    {
        // Check authorization based on message ownership and role
        $this->authorize('delete', $message);

        return $this->messengerService->deleteMessage($message->id);
    }

    // ... other actions (search, report, mark as read, etc.)

    public function reportMessage(Message $message)
    {
        // Handle message reporting logic
        // ...

        return response()->json([], 202); // Accepted
    }

    public function markAsRead(Message $message)
    {
        // Update read status for current user
        // ...

        return response()->json([], 200); // OK
    }

    // ... other methods as needed

    // private function authorize($action, $model, $args = [])
    // {
    //     // Implement role-based authorization logic here
    //     // Use Gate::allows or your preferred approach
    //     // Example:
    //     if ($action === 'create' && $conversation->is_private) {
    //         return Auth::user()->id === $conversation->user_id || $conversation->users->contains(Auth::user());
    //     }
    //     // ... other permission checks based on action, model, and roles
    // }
}
