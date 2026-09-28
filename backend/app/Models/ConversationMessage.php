<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    public const UPDATED_AT = null;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_ASSISTANT = 'assistant';

    /** Internal record of an agent tool call and its result; never shown to the customer. */
    public const ROLE_TOOL = 'tool';

    protected $fillable = [
        'role',
        'content',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function isVisibleToCustomer(): bool
    {
        // Tool results and the text a model attaches to its tool calls are internal working, not replies.
        return $this->role !== self::ROLE_TOOL
            && empty($this->meta['tool_calls'])
            && trim($this->content) !== '';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
