<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefundRequestRequest;
use App\Http\Resources\CustomerRefundResource;
use App\Services\Refunds\RefundRequestService;
use Illuminate\Http\JsonResponse;

class RefundRequestController extends Controller
{
    public function store(StoreRefundRequestRequest $request, RefundRequestService $service): JsonResponse
    {
        $refundRequest = $service->submit(
            $request->validated('email'),
            $request->validated('order_number'),
            $request->validated('message'),
        );

        return (new CustomerRefundResource($refundRequest))->response()->setStatusCode(201);
    }
}
