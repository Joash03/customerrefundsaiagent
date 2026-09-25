<?php

return [
    'window_days' => (int) env('REFUND_WINDOW_DAYS', 30),
    'auto_approve_limit' => (float) env('REFUND_AUTO_APPROVE_LIMIT', 500),
    'abuse_threshold' => (int) env('REFUND_ABUSE_THRESHOLD', 3),
    'abuse_lookback_days' => (int) env('REFUND_ABUSE_LOOKBACK_DAYS', 90),
    'min_confidence' => (float) env('REFUND_MIN_CONFIDENCE', 0.6),
    'max_message_length' => 2000,
];
