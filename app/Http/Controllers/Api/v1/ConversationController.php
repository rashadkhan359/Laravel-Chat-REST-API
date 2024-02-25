<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\ConversationActionType;
use App\Enums\ConversationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ConversationStoreRequest;
use App\Http\Requests\Api\v1\ConversationUpdateRequest;
use App\Http\Resources\v1\ConversationCollection;
use App\Http\Resources\v1\ConversationResource;
use App\Http\Resources\v1\UserCollection;
use App\Http\Responses\v1\ApiResponse;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use App\Services\ImageService;
use App\Services\v1\MessengerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Filter based on user involvement
        $conversations = Conversation::whereHas('users', function ($query) use ($request) {
            $query->where('user_id', Auth::id());
        })->with('users')->get();

        // Filter by type (if specified)
        if ($request->has('type')) {
            $conversations = $conversations->where('type', $request->type);
        }

        return ApiResponse::success(new ConversationCollection($conversations))->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ConversationStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(ConversationStoreRequest $request)
    {

        $messengerService = new MessengerService;

        if ($request->type == ConversationType::DIRECT) {
            // Check if a conversation already exists between the given users
            $existingConversation = Conversation::whereHas('users', function ($query) use ($request) {
                $query->whereIn('user_id', $request->user_ids);
            }, '=', count($request->user_ids))->first();

            if ($existingConversation) {
                // Conversation already exists, return it
                return ApiResponse::success(new ConversationResource($existingConversation), 'Existing conversation retrieved successfully.')->respond();
            }
        }

        // Create conversation and associate participants
        $conversation = Conversation::create($request->all());

        $conversation->users()->attach($request->user_ids);

        if ($request->hasFile('icon')) {
            $this->updateIcon($conversation, $request->file('icon'));
        }

        // Determine initial message sender
        $sender = Auth::user();

        $response = [
            'attachment' => null,
            'attachment_title' => null
        ];

        // If an attachment was provided, process it first before sending the message
        if ($request->hasFile('file')) {
            $response = $messengerService->handleFiles($request->file('file'));
        }

        // Create initial message (optional)
        if ($request->has('message') || $request->hasFile('file')) {

            $messengerService->newMessage($conversation, [
                'sender_id' => $sender->id,
                'conversation_id' => $conversation->id,
                'content' => htmlentities(trim($request->message), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($response['attachment']) ? json_encode((object)[
                    'new_name' => $response['attachment'],
                    'old_name' => htmlentities(trim($response['attachment_title']), ENT_QUOTES, 'UTF-8'),
                ]) : null,
            ]);
        }

        return ApiResponse::success(new ConversationResource($conversation), 'Conversation created successfully.')->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ConversationUpdateRequest $request
     * @param Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(ConversationUpdateRequest $request, Conversation $conversation)
    {

        $this->authorize('update', [$conversation, $request->action, $request->user_id]);


        if ($request->action == ConversationActionType::UPDATE_ICON) {
            $this->updateIcon($conversation, $request->file('icon'));
        }

        if ($request->action == ConversationActionType::UPDATE_NAME) {
            $this->updateName($conversation, $request->input('name'));
        }

        if ($request->action == ConversationActionType::ADD_USER || $request->action == ConversationActionType::REMOVE_USER) {
            $this->updateParticipants($conversation, $request->action, $request->input('user_id'));
        }

        return ApiResponse::success(
            new ConversationResource($conversation),
            'Conversation updated successfully.'
        )->respond();
    }

    /**
     * Block a user within a conversation.
     *
     * @param Request $request
     * @param Conversation $conversation
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockUser(Request $request, Conversation $conversation, User $user)
    {
        // Ensure user is authorized and involved in the conversation
        if (!$conversation->users->contains(Auth::user()) || !$conversation->users->contains($user)) {
            return response()->json(['error' => 'Unauthorized to block this user.'], 403);
        }

        // Block the user
        // Use the "updateOrCreate" method with conditions
        $conversationUser = ConversationUser::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'is_blocked' => true,
            ]
        );

        return ApiResponse::noContent('User blocked successfuly.');
    }

    private function updateIcon(Conversation $conversation, $file)
    {
        $imageService = new ImageService();
        $icon = $imageService->storeImage($file, config('messenger.group_avatar.folder'));

        // Update conversation
        $conversation->update([
            'icon' => $icon['image'],
            'icon_thumbnail' => $icon['thumbnail'],
        ]);
    }

    private function updateName(Conversation $conversation, string $name)
    {
        $conversation->update([
            'name' => $name,
        ]);
    }


    private function updateParticipants(Conversation $conversation, ConversationActionType $action, int $acted_user)
    {
    }
}
