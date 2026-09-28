<?php

namespace App\Services\Support;

use App\Enums\ConversationStage;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Ai\InputGuard;
use App\Services\Ai\LlmClient;
use App\Services\Ai\ReplyWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * The AI support agent: the model runs the conversation and decides what to do next,
 * acting only through SupportTools. Instructions live in resources/ai/support-agent.md.
 */
class SupportAgent
{
    private const MAX_STEPS = 8;

    private const UNAVAILABLE = "Sorry, I'm having trouble right now. Please try again in a moment, or email our support team if it keeps happening.";

    public function __construct(
        private readonly LlmClient $llm,
        private readonly SupportTools $tools,
        private readonly InputGuard $guard,
        private readonly AssistantReplies $copy,
        private readonly ReplyWriter $replies,
    ) {}

    /**
     * Handle one customer message and return the messages the customer should see.
     *
     * @return list<ConversationMessage>
     */
    public function reply(Conversation $conversation, string $text): array
    {
        $screened = $this->guard->screen($text);
        $conversation->risk_flags = array_values(array_unique([...($conversation->risk_flags ?? []), ...$screened->flags]));
        $visible = [$this->store($conversation, ConversationMessage::ROLE_CUSTOMER, $screened->message)];

        if ($conversation->stage === ConversationStage::HandedOff) {
            $visible[] = $this->store($conversation, ConversationMessage::ROLE_ASSISTANT, $this->copy->handedOff());
            $conversation->save();

            return $visible;
        }

        $this->tools->submitted = null;
        $messages = $this->history($conversation);

        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            $result = $this->llm->chatWithTools($messages, $this->tools->definitions());

            if (! $result->succeeded()) {
                Log::warning('Support agent unavailable', ['conversation' => $conversation->token, 'failures' => $result->failures]);
                break;
            }

            $message = $result->data;
            $toolCalls = $message['tool_calls'] ?? [];

            if ($toolCalls === []) {
                // Some models leave stage directions such as "(Waiting for user response)" in the text.
                // The chat shows plain text, so markdown emphasis is removed too.
                $reply = trim(preg_replace(['/\s*\((waiting|awaiting)[^)]*\)/i', '/\*\*(.+?)\*\*/s'], ['', '$1'], (string) $message['content']) ?? '');
                $visible[] = $this->finish($conversation, $reply !== '' ? $reply : self::UNAVAILABLE);

                return $visible;
            }

            $this->store($conversation, ConversationMessage::ROLE_ASSISTANT, (string) ($message['content'] ?? ''), ['tool_calls' => $toolCalls]);
            $messages[] = ['role' => 'assistant', 'content' => null, 'tool_calls' => $toolCalls];

            foreach ($toolCalls as $call) {
                $arguments = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $output = $this->tools->execute($conversation, $call['function']['name'], $arguments, $result->provider);
                $encoded = json_encode($output);

                $this->store($conversation, ConversationMessage::ROLE_TOOL, $encoded, [
                    'tool_call_id' => $call['id'],
                    'name' => $call['function']['name'],
                    'arguments' => $arguments,
                ]);
                $messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => $encoded];
            }
        }

        // If a request was already recorded, still tell the customer the real outcome.
        $submitted = $this->tools->submitted;
        $visible[] = $this->finish($conversation, $submitted
            ? $this->replies->template($submitted->decision, $conversation->customer?->first_name, $submitted->reference)
            : self::UNAVAILABLE);

        return $visible;
    }

    private function finish(Conversation $conversation, string $content): ConversationMessage
    {
        $submitted = $this->tools->submitted;
        $meta = $submitted && $submitted->order_item_id !== null
            ? ['decision' => $submitted->decision->value, 'reference' => $submitted->reference]
            : [];

        $submitted?->update(['customer_reply' => $content]);

        $message = $this->store($conversation, ConversationMessage::ROLE_ASSISTANT, $content, $meta);
        $conversation->save();

        return $message;
    }

    /**
     * Rebuild the model's view of the conversation: instructions, then every turn and tool exchange.
     *
     * @return list<array<string, mixed>>
     */
    private function history(Conversation $conversation): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($conversation)]];

        foreach ($conversation->messages()->get() as $row) {
            $messages[] = match ($row->role) {
                ConversationMessage::ROLE_CUSTOMER => ['role' => 'user', 'content' => $row->content],
                ConversationMessage::ROLE_TOOL => ['role' => 'tool', 'tool_call_id' => $row->meta['tool_call_id'], 'content' => $row->content],
                default => isset($row->meta['tool_calls'])
                    ? ['role' => 'assistant', 'content' => null, 'tool_calls' => $row->meta['tool_calls']]
                    : ['role' => 'assistant', 'content' => $row->content],
            };
        }

        return $messages;
    }

    private function systemPrompt(Conversation $conversation): string
    {
        $state = $conversation->customer_id !== null
            ? "Current state: the customer is verified as {$conversation->customer->first_name} ({$conversation->customer->email}). These are all of their orders; no others exist:\n".json_encode($this->tools->ordersPayload($conversation))
            : 'Current state: the customer is not verified yet. Failed verification attempts so far: '.$conversation->verification_attempts.' of '.SupportTools::MAX_VERIFICATION_ATTEMPTS.'.';

        return strtr(File::get(resource_path('ai/support-agent.md')), [
            '{{window_days}}' => (string) config('refunds.window_days'),
            '{{review_limit}}' => '$'.number_format((float) config('refunds.auto_approve_limit')),
            '{{support_email}}' => (string) config('refunds.support_email'),
            '{{conversation_state}}' => $state,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function store(Conversation $conversation, string $role, string $content, array $meta = []): ConversationMessage
    {
        return $conversation->messages()->create(['role' => $role, 'content' => $content, 'meta' => $meta ?: null]);
    }
}
