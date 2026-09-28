<?php

namespace App\Services\Support;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Ai\LlmClient;

/**
 * Entry point for the support chat. With an LLM configured, the AI agent runs the conversation;
 * without one, the guided (scripted) flow keeps the chat working. The mode is fixed per conversation.
 */
class SupportChat
{
    public const MODE_AGENT = 'agent';

    public const MODE_GUIDED = 'guided';

    public function __construct(
        private readonly LlmClient $llm,
        private readonly SupportAgent $agent,
        private readonly ConversationService $guided,
    ) {}

    public function start(): Conversation
    {
        $conversation = $this->guided->start();
        $conversation->context = ['mode' => $this->llm->isConfigured() ? self::MODE_AGENT : self::MODE_GUIDED];
        $conversation->save();

        return $conversation;
    }

    /**
     * @return list<ConversationMessage>
     */
    public function reply(Conversation $conversation, string $text): array
    {
        $messages = ($conversation->context['mode'] ?? self::MODE_GUIDED) === self::MODE_AGENT
            ? $this->agent->reply($conversation, $text)
            : $this->guided->reply($conversation, $text);

        return array_values(array_filter($messages, fn (ConversationMessage $message) => $message->isVisibleToCustomer()));
    }
}
