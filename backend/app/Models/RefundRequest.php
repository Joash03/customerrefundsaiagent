<?php

namespace App\Models;

use App\Enums\RefundDecision;
use App\Enums\RefundReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefundRequest extends Model
{
    protected $fillable = [
        'reference',
        'customer_id',
        'order_id',
        'order_item_id',
        'submitted_email',
        'submitted_order_number',
        'message',
        'ai_reason',
        'ai_confidence',
        'ai_flags',
        'ai_summary',
        'ai_provider',
        'refund_amount',
        'decision',
        'matched_rules',
        'customer_reply',
        'final_decision',
    ];

    protected function casts(): array
    {
        return [
            'ai_reason' => RefundReason::class,
            'ai_confidence' => 'float',
            'ai_flags' => 'array',
            'refund_amount' => 'decimal:2',
            'decision' => RefundDecision::class,
            'final_decision' => RefundDecision::class,
            'matched_rules' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isAwaitingReview(): bool
    {
        return $this->decision === RefundDecision::Escalated && $this->final_decision === null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('id');
    }
}
