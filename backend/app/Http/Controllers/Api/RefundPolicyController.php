<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Serves the live policy values so the public policy page always matches what the engine enforces.
 */
class RefundPolicyController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'window_days' => config('refunds.window_days'),
            'auto_approve_limit' => config('refunds.auto_approve_limit'),
        ]);
    }
}
