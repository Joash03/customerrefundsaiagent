<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RefundDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListRefundRequestsRequest;
use App\Http\Requests\Admin\ReviewRefundRequestRequest;
use App\Http\Resources\RefundRequestResource;
use App\Models\RefundRequest;
use App\Services\Refunds\RefundReviewService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RefundRequestController extends Controller
{
    public function index(ListRefundRequestsRequest $request): AnonymousResourceCollection
    {
        $refundRequests = RefundRequest::query()
            ->with(['customer', 'order', 'orderItem'])
            ->when($request->validated('decision'), fn ($query, $decision) => $query->where('decision', $decision))
            ->when($request->boolean('awaiting_review'), fn ($query) => $query
                ->where('decision', RefundDecision::Escalated)
                ->whereNull('final_decision'))
            ->when($request->validated('search'), fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('submitted_email', 'like', "%{$search}%")
                ->orWhere('submitted_order_number', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(15);

        return RefundRequestResource::collection($refundRequests);
    }

    public function show(RefundRequest $refundRequest): RefundRequestResource
    {
        return new RefundRequestResource($refundRequest->load(['customer', 'order', 'orderItem', 'reviewer', 'auditLogs']));
    }

    public function review(ReviewRefundRequestRequest $request, RefundRequest $refundRequest, RefundReviewService $service): RefundRequestResource
    {
        $reviewed = $service->review(
            $refundRequest,
            $request->user(),
            RefundDecision::from($request->validated('decision')),
            $request->validated('note'),
        );

        return new RefundRequestResource($reviewed->load(['customer', 'order', 'orderItem', 'reviewer', 'auditLogs']));
    }
}
