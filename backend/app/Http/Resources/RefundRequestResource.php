<?php

namespace App\Http\Resources;

use App\Enums\PolicyRule;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RefundRequest
 */
class RefundRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ] : null),
            'submitted_email' => $this->submitted_email,
            'submitted_order_number' => $this->submitted_order_number,
            'order' => $this->whenLoaded('order', fn () => $this->order ? [
                'order_number' => $this->order->order_number,
                'status' => $this->order->status->value,
                'delivered_at' => $this->order->delivered_at?->toIso8601String(),
                'total' => $this->order->total,
            ] : null),
            'item' => $this->whenLoaded('orderItem', fn () => $this->orderItem ? [
                'product_name' => $this->orderItem->product_name,
                'category' => $this->orderItem->category,
                'is_final_sale' => $this->orderItem->is_final_sale,
            ] : null),
            'message' => $this->message,
            'ai' => [
                'provider' => $this->ai_provider,
                'reason' => $this->ai_reason?->value,
                'confidence' => $this->ai_confidence,
                'flags' => $this->ai_flags ?? [],
                'summary' => $this->ai_summary,
            ],
            'refund_amount' => $this->refund_amount,
            'decision' => $this->decision->value,
            'final_decision' => $this->final_decision?->value,
            'awaiting_review' => $this->isAwaitingReview(),
            'rules' => collect($this->matched_rules)->map(fn (string $id) => [
                'id' => $id,
                'description' => PolicyRule::from($id)->description(),
            ]),
            'customer_reply' => $this->customer_reply,
            'review' => $this->reviewed_at ? [
                'reviewer' => $this->reviewer?->name,
                'reviewed_at' => $this->reviewed_at->toIso8601String(),
                'note' => $this->review_note,
            ] : null,
            'audit_logs' => $this->whenLoaded('auditLogs', fn () => $this->auditLogs->map(fn ($log) => [
                'step' => $log->step,
                'payload' => $log->payload,
                'created_at' => $log->created_at->toIso8601String(),
            ])),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
