<?php

namespace App\Services\Support;

use App\Enums\ConversationStage;
use App\Enums\RefundReason;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Ai\Classification;
use App\Services\Ai\InputGuard;
use App\Services\Ai\RefundClassifier;
use App\Services\Refunds\RefundRequestService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Support chat as a state machine. Code owns the flow (identify -> describe -> confirm -> decide);
 * the AI only interprets what the customer writes and drafts the decision reply.
 */
class ConversationService
{
    public const MAX_VERIFICATION_ATTEMPTS = 3;

    public const MAX_CLARIFICATIONS = 2;

    private const EMAIL_PATTERN = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i';

    private const ORDER_PATTERN = '/\bORD[-\s]?(\d{5})\b/i';

    private const YES_PATTERN = '/^\s*(yes|yeah|yep|correct|confirm(ed)?|that\'?s right|right|sure|ok(ay)?|y)\b/i';

    private const NO_PATTERN = '/^\s*(no|nope|not quite|wrong|n)\b/i';

    /** Words that surround identity details without describing a problem. */
    private const IDENTITY_FILLER = ['hi', 'hello', 'hey', 'i', 'am', 'im', 'my', 'is', 'its', 'it', 'the', 'and', 'email', 'address', 'order', 'number', 'here', 'thanks', 'thank', 'you', 'please', 'name'];

    private const DONE_PATTERN = '/^\s*(no|nope|nothing|that\'?s all|that is all|all good|i\'?m good)\b/i';

    /** @var list<ConversationMessage> */
    private array $replies = [];

    public function __construct(
        private readonly InputGuard $guard,
        private readonly RefundClassifier $classifier,
        private readonly RefundRequestService $refunds,
        private readonly AssistantReplies $copy,
    ) {}

    public function start(): Conversation
    {
        $conversation = Conversation::create([
            'token' => (string) Str::uuid(),
            'stage' => ConversationStage::AwaitingIdentity,
            'context' => [],
            'risk_flags' => [],
        ]);

        $this->replies = [];
        $this->say($conversation, $this->copy->greeting());

        return $conversation;
    }

    /**
     * Handle one customer message and return every message created in this turn.
     *
     * @return list<ConversationMessage>
     */
    public function reply(Conversation $conversation, string $text): array
    {
        $this->replies = [];

        $screened = $this->guard->screen($text);
        $this->addRiskFlags($conversation, $screened->flags);
        $this->replies[] = $conversation->messages()->create(['role' => ConversationMessage::ROLE_CUSTOMER, 'content' => $screened->message]);

        if ($this->mentionsAnotherAccount($conversation, $screened->message)) {
            $this->say($conversation, $this->copy->anotherAccount());
            $conversation->save();

            return $this->replies;
        }

        match ($conversation->stage) {
            ConversationStage::AwaitingIdentity => $this->handleIdentity($conversation, $screened->message),
            ConversationStage::AwaitingIssue => $this->handleIssue($conversation, $screened->message),
            ConversationStage::AwaitingConfirmation => $this->handleConfirmation($conversation, $screened->message),
            ConversationStage::HandedOff => $this->say($conversation, $this->copy->handedOff()),
        };

        $conversation->save();

        return $this->replies;
    }

    /**
     * A verified chat stays bound to that customer; switching accounts needs a new chat.
     */
    private function mentionsAnotherAccount(Conversation $conversation, string $text): bool
    {
        if ($conversation->customer_id === null || ! preg_match(self::EMAIL_PATTERN, $text, $match)) {
            return false;
        }

        return Str::lower($match[0]) !== $conversation->customer->email;
    }

    private function handleIdentity(Conversation $conversation, string $text): void
    {
        $email = preg_match(self::EMAIL_PATTERN, $text, $emailMatch) ? Str::lower($emailMatch[0]) : ($conversation->context['email'] ?? null);
        $orderNumber = preg_match(self::ORDER_PATTERN, $text, $orderMatch) ? 'ORD-'.$orderMatch[1] : ($conversation->context['order_number'] ?? null);

        if ($email === null || $orderNumber === null) {
            $this->mergeContext($conversation, ['email' => $email, 'order_number' => $orderNumber]);
            $this->say($conversation, $this->copy->askForMissingIdentity($email, $orderNumber));

            return;
        }

        $order = $this->refunds->findVerifiedOrder($email, $orderNumber);

        if ($order === null) {
            $conversation->verification_attempts++;
            $this->mergeContext($conversation, ['email' => null, 'order_number' => null]);

            if ($conversation->verification_attempts >= self::MAX_VERIFICATION_ATTEMPTS) {
                $conversation->stage = ConversationStage::HandedOff;
                $this->say($conversation, $this->copy->handedOffAfterVerification());

                return;
            }

            $this->say($conversation, $this->copy->verificationFailed(self::MAX_VERIFICATION_ATTEMPTS - $conversation->verification_attempts));

            return;
        }

        $conversation->fill(['customer_id' => $order->customer_id, 'order_id' => $order->id, 'stage' => ConversationStage::AwaitingIssue]);
        $conversation->setRelation('order', $order)->setRelation('customer', $order->customer);
        $this->mergeContext($conversation, ['email' => null, 'order_number' => null]);

        // Customers often describe the problem in the same message as their details.
        $remainder = trim(preg_replace([self::EMAIL_PATTERN, self::ORDER_PATTERN], '', $text) ?? '');
        if ($this->describesProblem($remainder)) {
            $this->handleIssue($conversation, $remainder);

            return;
        }

        $this->say($conversation, $this->copy->verified($order));
    }

    private function describesProblem(string $text): bool
    {
        $words = str_word_count(Str::lower(str_replace("'", '', $text)), 1);

        return count(array_diff($words, self::IDENTITY_FILLER)) >= 3;
    }

    private function handleIssue(Conversation $conversation, string $text): void
    {
        $context = $conversation->context;

        if (($context['after_decision'] ?? false) && preg_match(self::DONE_PATTERN, $text)) {
            $this->mergeContext($conversation, ['after_decision' => false]);
            $this->say($conversation, $this->copy->goodbye($conversation->customer->first_name));

            return;
        }

        $issueMessages = [...($context['issue_messages'] ?? []), $text];
        $orders = $this->customerOrders($conversation);

        ['classification' => $classification, 'failures' => $failures] = $this->classifier->classify(
            implode("\n", $issueMessages),
            $orders,
            $conversation->risk_flags ?? [],
        );
        $this->addRiskFlags($conversation, array_intersect($classification->flags, ['injection_attempt', 'policy_pressure']));
        $this->mergeContext($conversation, ['issue_messages' => $issueMessages, 'after_decision' => false]);

        $item = $orders->flatMap->items->firstWhere('id', $classification->itemId);
        $unclearReason = $classification->reason === RefundReason::Other
            || $classification->confidence < config('refunds.min_confidence');
        $canClarify = $conversation->clarification_attempts < self::MAX_CLARIFICATIONS;

        if ($canClarify && ($item === null || $unclearReason)) {
            $conversation->clarification_attempts++;
            $this->askClarification($conversation, $classification, $item, $orders);

            return;
        }

        if ($item === null) {
            // Still unclear after clarifying: let the policy engine route it to a human.
            $this->decide($conversation, $classification, $failures);

            return;
        }

        $conversation->stage = ConversationStage::AwaitingConfirmation;
        $this->mergeContext($conversation, ['pending' => ['classification' => $classification->toArray(), 'failures' => $failures]]);
        $this->say($conversation, $this->copy->confirm($item, $classification->reason), ["Yes, that's right", "No, that's not it"]);
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function askClarification(Conversation $conversation, Classification $classification, ?OrderItem $item, Collection $orders): void
    {
        if ($item === null && $classification->productMentioned !== null) {
            $this->say(
                $conversation,
                $this->copy->productNotFound($classification->productMentioned, $orders),
                $orders->flatMap->items->pluck('product_name')->take(6)->all(),
            );

            return;
        }

        if ($item === null) {
            $this->say($conversation, $this->copy->askWhichItem($conversation->order), $conversation->order->items->pluck('product_name')->all());

            return;
        }

        $this->say($conversation, $this->copy->askForReason($item), $this->copy->reasonQuickReplies());
    }

    private function handleConfirmation(Conversation $conversation, string $text): void
    {
        $pending = $conversation->context['pending'] ?? null;

        if ($pending !== null && preg_match(self::YES_PATTERN, $text)) {
            $this->decide($conversation, Classification::fromArray($pending['classification']), $pending['failures']);

            return;
        }

        $this->resetIssue($conversation);

        if (preg_match(self::NO_PATTERN, $text)) {
            $this->say($conversation, $this->copy->restartIssue());

            return;
        }

        // Anything else is treated as a fresh description of the problem.
        $this->handleIssue($conversation, $text);
    }

    /**
     * @param  list<array{provider: string, error: string}>  $failures
     */
    private function decide(Conversation $conversation, Classification $classification, array $failures): void
    {
        $refundRequest = $this->refunds->submitFromConversation(
            $conversation,
            implode("\n", $conversation->context['issue_messages'] ?? []),
            $classification,
            $conversation->risk_flags ?? [],
            $failures,
        );

        $this->resetIssue($conversation);
        $this->mergeContext($conversation, ['after_decision' => true]);

        $this->say($conversation, $refundRequest->customer_reply, meta: [
            'decision' => $refundRequest->decision->value,
            'reference' => $refundRequest->reference,
        ]);
        $this->say($conversation, $this->copy->anythingElse(), ["No, that's all"]);
    }

    /**
     * The verified order first, then the customer's other recent orders.
     *
     * @return Collection<int, Order>
     */
    private function customerOrders(Conversation $conversation): Collection
    {
        return Order::with('items')
            ->where('customer_id', $conversation->customer_id)
            ->orderByRaw('id = ? desc', [$conversation->order_id])
            ->latest('ordered_at')
            ->limit(10)
            ->get();
    }

    private function resetIssue(Conversation $conversation): void
    {
        $conversation->stage = ConversationStage::AwaitingIssue;
        $conversation->clarification_attempts = 0;
        $this->mergeContext($conversation, ['issue_messages' => [], 'pending' => null]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function mergeContext(Conversation $conversation, array $values): void
    {
        $conversation->context = array_merge($conversation->context ?? [], $values);
    }

    /**
     * @param  iterable<string>  $flags
     */
    private function addRiskFlags(Conversation $conversation, iterable $flags): void
    {
        $conversation->risk_flags = array_values(array_unique([...($conversation->risk_flags ?? []), ...$flags]));
    }

    /**
     * @param  list<string>  $quickReplies
     * @param  array<string, mixed>  $meta
     */
    private function say(Conversation $conversation, string $content, array $quickReplies = [], array $meta = []): void
    {
        if ($quickReplies !== []) {
            $meta['quick_replies'] = $quickReplies;
        }

        $this->replies[] = $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_ASSISTANT,
            'content' => $content,
            'meta' => $meta ?: null,
        ]);
    }
}
