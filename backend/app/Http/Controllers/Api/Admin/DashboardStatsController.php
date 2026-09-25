<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RefundDecision;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use Illuminate\Http\JsonResponse;

class DashboardStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $counts = RefundRequest::query()
            ->selectRaw('decision, count(*) as total')
            ->groupBy('decision')
            ->pluck('total', 'decision');

        return response()->json([
            'total' => $counts->sum(),
            'approved' => (int) ($counts[RefundDecision::Approved->value] ?? 0),
            'denied' => (int) ($counts[RefundDecision::Denied->value] ?? 0),
            'escalated' => (int) ($counts[RefundDecision::Escalated->value] ?? 0),
            'awaiting_review' => RefundRequest::where('decision', RefundDecision::Escalated)->whereNull('final_decision')->count(),
            'ai_flagged' => RefundRequest::whereJsonLength('ai_flags', '>', 0)->count(),
        ]);
    }
}
