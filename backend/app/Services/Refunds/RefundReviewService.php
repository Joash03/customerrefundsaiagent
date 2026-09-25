<?php

namespace App\Services\Refunds;

use App\Enums\RefundDecision;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundReviewService
{
    public function review(RefundRequest $refundRequest, User $reviewer, RefundDecision $decision, ?string $note): RefundRequest
    {
        return DB::transaction(function () use ($refundRequest, $reviewer, $decision, $note) {
            $refundRequest = RefundRequest::lockForUpdate()->findOrFail($refundRequest->id);

            if (! $refundRequest->isAwaitingReview()) {
                throw ValidationException::withMessages(['decision' => 'This request is not awaiting review.']);
            }

            $refundRequest->forceFill([
                'final_decision' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            if ($decision === RefundDecision::Approved) {
                $refundRequest->orderItem?->update(['refunded_at' => now()]);
            }

            $refundRequest->auditLogs()->create([
                'step' => 'admin_reviewed',
                'payload' => ['decision' => $decision->value, 'reviewer' => $reviewer->email, 'note' => $note],
            ]);

            return $refundRequest;
        });
    }
}
