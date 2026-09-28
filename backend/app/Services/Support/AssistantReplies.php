<?php

namespace App\Services\Support;

use App\Enums\OrderStatus;
use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Wording for the procedural turns of the support chat. Every fact shown here
 * (orders, items, dates) comes from the database, never from the model.
 */
class AssistantReplies
{
    public function greeting(): string
    {
        return "Hi there! What can I help you with today?\n\nIf it's about an order, I'll need the email address and order number from your confirmation email (it looks like ORD-12345).";
    }

    public function askForMissingIdentity(?string $email, ?string $orderNumber): string
    {
        return match (true) {
            $email === null && $orderNumber === null => 'To look up your order I need the email address you used at checkout and your order number (for example ORD-12345).',
            $email === null => "Thanks, I have order {$orderNumber}. What email address did you use when placing it?",
            default => 'Thanks. What is the order number? You can find it in your confirmation email (for example ORD-12345).',
        };
    }

    public function verificationFailed(int $attemptsLeft): string
    {
        return "I couldn't find an order matching that email address and order number. Please check both and try again ({$attemptsLeft} ".($attemptsLeft === 1 ? 'attempt' : 'attempts').' left).';
    }

    public function handedOffAfterVerification(): string
    {
        return "I'm sorry, I still can't match those details, so for your security I can't continue here. Our support team will need to verify your identity. Please email support with your order confirmation attached and we'll get back to you within one business day.";
    }

    public function handedOff(): string
    {
        return 'This conversation has been passed to our support team. Please start a new conversation if you need help with anything else.';
    }

    public function anotherAccount(): string
    {
        return 'For your security, this chat is linked to the account you already verified. To get help with a different account, please start a new chat.';
    }

    public function verified(Order $order): string
    {
        return "Thanks, {$order->customer->first_name}! I've found your order {$this->orderLine($order)}.\n\nWhat went wrong with your order?";
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function productNotFound(string $product, Collection $orders): string
    {
        return "I couldn't find \"{$product}\" on your orders. Here is what I can see on your account:\n{$this->orderList($orders)}\n\nIs it one of these items I can help you with?";
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function askWhichItem(Collection $orders): string
    {
        return "Which item is this about? Here is what I can see on your account:\n{$this->orderList($orders)}\n\nIf it's about a different order, send me its order number.";
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function orderNotOnAccount(string $orderNumber, Collection $orders): string
    {
        return "I can't find order {$orderNumber} on your account. Here is what I can see:\n{$this->orderList($orders)}\n\nIs it one of these items you need help with?";
    }

    public function notARefund(): string
    {
        return "Understood. Here I can help with refunds for items that arrived damaged, were wrong, haven't arrived, or that you no longer want. For exchanges or anything else, please email our support team and they'll help you.\n\nIs there a refund I can help you with?";
    }

    public function handedOffUnclear(string $firstName): string
    {
        return "Thanks, {$firstName}. I haven't been able to work out exactly which item this is about, so I've passed your conversation to our support team. They'll review it and get back to you by email within 1-2 business days.";
    }

    public function askForReason(?OrderItem $item): string
    {
        $subject = $item ? "the {$item->product_name}" : 'the item';

        return "Could you tell me a little more about what's wrong with {$subject}? For example, did it arrive damaged, is it the wrong item, has it not arrived, or have you changed your mind?";
    }

    public function confirm(OrderItem $item, RefundReason $reason): string
    {
        return "Just to confirm: you'd like a refund for the {$item->product_name} from order {$item->order->order_number} because {$this->reasonPhrase($reason)}. Is that right?";
    }

    public function restartIssue(): string
    {
        return 'No problem. Please describe the issue again, including which item it is about.';
    }

    public function anythingElse(): string
    {
        return 'Is there anything else I can help you with?';
    }

    public function goodbye(string $firstName): string
    {
        return "Thanks for contacting us, {$firstName}. Have a great day!";
    }

    /**
     * @return list<string>
     */
    public function reasonQuickReplies(): array
    {
        return ['It arrived damaged', 'I received the wrong item', "It hasn't arrived", "I've changed my mind"];
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function orderList(Collection $orders): string
    {
        return $orders->map(fn (Order $order) => '• '.$this->orderLine($order))->implode("\n");
    }

    public function orderLine(Order $order): string
    {
        $status = match ($order->status) {
            OrderStatus::Delivered => 'delivered on '.$order->delivered_at?->format('j M Y'),
            OrderStatus::Shipped => 'shipped, not yet delivered',
            OrderStatus::Processing => 'still being processed',
            OrderStatus::Cancelled => 'cancelled',
        };

        return "{$order->order_number} ({$status}): ".$order->items->pluck('product_name')->implode(', ');
    }

    private function reasonPhrase(RefundReason $reason): string
    {
        return match ($reason) {
            RefundReason::Damaged => 'it arrived damaged or faulty',
            RefundReason::WrongItem => 'you received the wrong item',
            RefundReason::ChangedMind => "you've changed your mind",
            RefundReason::NotReceived => "it hasn't arrived",
            RefundReason::Other => 'of a problem with the item',
        };
    }
}
