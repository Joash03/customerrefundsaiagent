<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer-facing view of a conversation: no risk flags or internal context.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->token,
            'stage' => $this->stage->value,
            'messages' => ConversationMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
