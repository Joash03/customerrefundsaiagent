<?php

namespace App\Http\Resources;

use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer-facing view: no rule ids, AI flags or internal notes.
 *
 * @mixin RefundRequest
 */
class CustomerRefundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'decision' => $this->decision->value,
            'reply' => $this->customer_reply,
            'submitted_at' => $this->created_at->toIso8601String(),
        ];
    }
}
