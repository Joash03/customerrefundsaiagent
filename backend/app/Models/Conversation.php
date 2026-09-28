<?php

namespace App\Models;

use App\Enums\ConversationStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'token',
        'stage',
        'customer_id',
        'order_id',
        'verification_attempts',
        'clarification_attempts',
        'context',
        'risk_flags',
    ];

    protected function casts(): array
    {
        return [
            'stage' => ConversationStage::class,
            'context' => 'array',
            'risk_flags' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }
}
