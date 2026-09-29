<?php

namespace Tests\Unit;

use App\Services\Support\SupportAgent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AgentReplyCleaningTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function replies(): array
    {
        return [
            'run-together replies (seen live)' => [
                'Thanks! Could you also provide the order number that goes with that email (it should look like ORD‑XXXXX)?Sure thing—just let me know the order number from your confirmation (e.g., ORD‑12345).............I’m still missing the order number—could you copy the exact order ID from your confirmation email (it starts with “ORD‑”)?',
                'Thanks! Could you also provide the order number that goes with that email (it should look like ORD‑XXXXX)?',
            ],
            'normal multi-sentence reply is kept' => [
                'Sorry about that, Ava. The headphones qualify for a refund of $89.99. Would you like me to submit it?',
                'Sorry about that, Ava. The headphones qualify for a refund of $89.99. Would you like me to submit it?',
            ],
            'list and reference are kept' => [
                "Here is what I can see:\n- ORD-10016: Linen Bedsheet Set\n- ORD-10017: Bamboo Bath Towels\n\nReference: RF-AB12CD34.",
                "Here is what I can see:\n- ORD-10016: Linen Bedsheet Set\n- ORD-10017: Bamboo Bath Towels\n\nReference: RF-AB12CD34.",
            ],
            'stage direction removed' => [
                'What went wrong with the shoes?(Waiting for user response)',
                'What went wrong with the shoes?',
            ],
            'markdown emphasis removed' => [
                'Your order is still **processing**.',
                'Your order is still processing.',
            ],
        ];
    }

    #[DataProvider('replies')]
    public function test_clean_reply(string $raw, string $expected): void
    {
        $this->assertSame($expected, app(SupportAgent::class)->cleanReply($raw));
    }
}
