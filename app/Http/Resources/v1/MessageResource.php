<?php

namespace App\Http\Resources\v1;

use App\Traits\v1\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    use Contact;


    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $attachment = null;
        $attachment_type = null;
        $attachment_title = null;
        if (isset($this->attachment)) {
            $attachmentOBJ = json_decode($this->attachment);
            $attachment = $attachmentOBJ->new_name;
            $attachment_title = htmlentities(trim($attachmentOBJ->old_name), ENT_QUOTES, 'UTF-8');
            $ext = pathinfo($attachment, PATHINFO_EXTENSION);
            $attachment_type = in_array($ext, $this->getAllowedImages()) ? 'image' : 'file';
        }
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation_id,
            'senderId' => $this->sender_id,
            'message' => $this->content,
            'attachment' => (object) [
                'file' => $attachment,
                'title' => $attachment_title,
                'type' => $attachment_type
            ],
            'timeAgo' => $this->created_at->diffForHumans(),
            'createdAt' => $this->created_at->toIso8601String(),
            'seenBy' => $this->seen_by,
        ];
    }
}
