<?php

namespace App\Http\Resources;

use App\Models\ConversationMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConversationMessage
 */
class ConversationMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'quick_replies' => $this->meta['quick_replies'] ?? [],
            'decision' => $this->meta['decision'] ?? null,
            'reference' => $this->meta['reference'] ?? null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
