<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>  $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'icon' => $this->icon,
            'lastMessage' => new MessageResource($this->messages()->latest()->first()),
            'users' => new UserCollection($this->users),
        ];
    }
}
