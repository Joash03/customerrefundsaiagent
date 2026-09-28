<?php

namespace App\Services\Refunds;

use App\Enums\RefundDecision;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RefundRequest;
use App\Services\Ai\Classification;
use App\Services\Ai\InputGuard;
use App\Services\Ai\RefundClassifier;
use App\Services\Ai\ReplyWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pipeline: screen input -> verify order -> AI classification -> policy decision -> AI reply -> persist + audit.
 * Used directly by the one-shot API and, after the chat has gathered the facts, by the conversation flow.
 */
class RefundRequestService
{
    /** @var list<array{step: string, payload: array<string, mixed>}> */
    private array $audit = [];

    public function __construct(
        private readonly InputGuard $guard,
        private readonly RefundClassifier $classifier,
        private readonly PolicyEngine $policy,
        private readonly ReplyWriter $replyWriter,
    ) {}

    public function submit(string $email, string $orderNumber, string $message): RefundRequest
    {
        $this->audit = [];

        $screened = $this->guard->screen($message);
        $this->log('input_screened', ['flags' => $screened->flags, 'patterns' => $screened->matchedPatterns]);

        $order = $this->findVerifiedOrder($email, $orderNumber);
        $this->log('order_verified', ['verified' => $order !== null]);

        $classification = null;
        if ($order !== null) {
            ['classification' => $classification, 'failures' => $failures] = $this->classifier->classify($screened->message, collect([$order]), $screened->flags);
            $this->logClassification($classification, $failures);
        }

        return $this->decide($order, $classification, $screened->flags, [
            'submitted_email' => $email,
            'submitted_order_number' => $orderNumber,
            'message' => $screened->message,
        ]);
    }

    /**
     * Decide a request whose facts were gathered and confirmed in a chat conversation.
     *
     * @param  list<string>  $riskFlags  Flags accumulated across the whole conversation.
     * @param  list<array{provider: string, error: string}>  $failures
     */
    public function submitFromConversation(Conversation $conversation, string $message, Classification $classification, array $riskFlags, array $failures): RefundRequest
    {
        $this->audit = [];
        $this->log('conversation_verified', ['conversation' => $conversation->token, 'order' => $conversation->order->order_number]);
        $this->logClassification($classification, $failures);

        return $this->decide($conversation->order, $classification, $riskFlags, [
            'conversation_id' => $conversation->id,
            'submitted_email' => $conversation->customer->email,
            'submitted_order_number' => $conversation->order->order_number,
            'message' => $message,
        ]);
    }

    public function findVerifiedOrder(string $email, string $orderNumber): ?Order
    {
        return Order::query()
            ->with(['items', 'customer'])
            ->where('order_number', Str::upper(trim($orderNumber)))
            ->whereHas('customer', fn ($query) => $query->where('email', Str::lower(trim($email))))
            ->first();
    }

    /**
     * @param  list<string>  $inputFlags
     * @param  array<string, mixed>  $attributes
     */
    private function decide(?Order $verifiedOrder, ?Classification $classification, array $inputFlags, array $attributes): RefundRequest
    {
        $reference = 'RF-'.Str::upper(Str::random(8));
        $item = $classification?->itemId ? $this->findCustomerItem($verifiedOrder, $classification->itemId) : null;
        $order = $item?->order ?? $verifiedOrder;

        $context = $this->buildContext($order, $item, $classification, $inputFlags);
        $result = $this->policy->evaluate($context, now());
        $this->log('policy_evaluated', [
            'decision' => $result->decision->value,
            'rules' => $result->ruleIds(),
            'signals' => $result->signals,
            'refund_amount' => $context->refundAmount,
        ]);

        $reply = $this->replyWriter->write($result, $order?->customer->first_name, $item?->product_name, $reference);
        $this->log('reply_generated', ['provider' => $reply['provider'], 'provider_failures' => $reply['failures']]);

        return DB::transaction(function () use ($reference, $attributes, $order, $item, $classification, $context, $result, $reply) {
            $refundRequest = RefundRequest::create([
                ...$attributes,
                'reference' => $reference,
                'customer_id' => $order?->customer_id,
                'order_id' => $order?->id,
                'order_item_id' => $item?->id,
                'ai_reason' => $classification?->reason,
                'ai_confidence' => $classification?->confidence,
                'ai_flags' => $classification?->flags,
                'ai_summary' => $classification?->summary,
                'ai_provider' => $classification?->provider,
                'refund_amount' => $context->refundAmount,
                'decision' => $result->decision,
                'matched_rules' => $result->ruleIds(),
                'customer_reply' => $reply['reply'],
                'final_decision' => $result->decision === RefundDecision::Escalated ? null : $result->decision,
            ]);

            if ($result->decision === RefundDecision::Approved) {
                $item->update(['refunded_at' => now()]);
                $this->log('item_refunded', ['order_item_id' => $item->id, 'amount' => $context->refundAmount]);
            }

            $refundRequest->auditLogs()->createMany($this->audit);

            return $refundRequest;
        });
    }

    /**
     * The item must belong to the verified customer; it may sit on another of their orders.
     */
    private function findCustomerItem(?Order $verifiedOrder, int $itemId): ?OrderItem
    {
        if ($verifiedOrder === null) {
            return null;
        }

        return OrderItem::with('order.customer')
            ->whereKey($itemId)
            ->whereHas('order', fn ($query) => $query->where('customer_id', $verifiedOrder->customer_id))
            ->first();
    }

    /**
     * @param  list<string>  $inputFlags
     */
    private function buildContext(?Order $order, ?OrderItem $item, ?Classification $classification, array $inputFlags): PolicyContext
    {
        if ($order === null) {
            return new PolicyContext(orderVerified: false, inputFlags: $inputFlags);
        }

        return new PolicyContext(
            orderVerified: true,
            orderStatus: $order->status,
            deliveredAt: $order->delivered_at,
            itemIdentified: $item !== null,
            itemIsFinalSale: (bool) $item?->is_final_sale,
            refundAmount: $item?->lineTotal(),
            itemAlreadyRefundedOrPending: $item !== null && $this->isRefundedOrPending($item),
            itemPreviouslyDenied: $item !== null && RefundRequest::where('order_item_id', $item->id)
                ->where('final_decision', RefundDecision::Denied)
                ->exists(),
            recentRefundCount: OrderItem::query()
                ->whereHas('order', fn ($query) => $query->where('customer_id', $order->customer_id))
                ->where('refunded_at', '>=', now()->subDays(config('refunds.abuse_lookback_days')))
                ->count(),
            classification: $classification,
            inputFlags: $inputFlags,
        );
    }

    private function isRefundedOrPending(OrderItem $item): bool
    {
        return $item->refunded_at !== null
            || RefundRequest::where('order_item_id', $item->id)
                ->where('decision', RefundDecision::Escalated)
                ->whereNull('final_decision')
                ->exists();
    }

    /**
     * @param  list<array{provider: string, error: string}>  $failures
     */
    private function logClassification(Classification $classification, array $failures): void
    {
        $this->log('ai_classified', [
            'provider' => $classification->provider,
            'reason' => $classification->reason->value,
            'confidence' => $classification->confidence,
            'item_id' => $classification->itemId,
            'flags' => $classification->flags,
            'summary' => $classification->summary,
            'provider_failures' => $failures,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(string $step, array $payload): void
    {
        $this->audit[] = ['step' => $step, 'payload' => $payload];
    }
}
