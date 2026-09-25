<?php

namespace Tests\Feature;

use App\Models\RefundRequest;
use App\Models\User;
use Database\Seeders\RefundScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRefundRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RefundScenarioSeeder::class);
        config(['llm.order' => ['groq', 'grok'], 'llm.providers.grok.api_key' => null, 'llm.providers.groq.api_key' => null]);
    }

    private function escalatedRequest(): RefundRequest
    {
        $this->postJson('/api/refund-requests', [
            'email' => 'olivia.kim@example.com',
            'order_number' => 'ORD-10007',
            'message' => 'The laptop screen is cracked on arrival.',
        ])->assertJsonPath('data.decision', 'escalated');

        return RefundRequest::latest('id')->first();
    }

    public function test_admin_endpoints_require_authentication(): void
    {
        $this->getJson('/api/admin/refund-requests')->assertUnauthorized();
        $this->getJson('/api/admin/stats')->assertUnauthorized();
    }

    public function test_admin_can_log_in_and_receive_a_token(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);

        $this->postJson('/api/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertUnprocessable();

        $this->postJson('/api/admin/login', ['email' => 'admin@example.com', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['name', 'email']]);
    }

    public function test_list_show_and_stats(): void
    {
        $escalated = $this->escalatedRequest();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/refund-requests?awaiting_review=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $escalated->reference);

        $this->getJson("/api/admin/refund-requests/{$escalated->id}")
            ->assertOk()
            ->assertJsonPath('data.rules.0.id', 'R6')
            ->assertJsonPath('data.awaiting_review', true)
            ->assertJsonCount(5, 'data.audit_logs');

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('escalated', 2)
            ->assertJsonPath('awaiting_review', 1);
    }

    public function test_admin_can_approve_an_escalated_request_once(): void
    {
        $escalated = $this->escalatedRequest();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/admin/refund-requests/{$escalated->id}/review", ['decision' => 'approved', 'note' => 'Photos confirm damage.'])
            ->assertOk()
            ->assertJsonPath('data.final_decision', 'approved')
            ->assertJsonPath('data.review.note', 'Photos confirm damage.');

        $this->assertNotNull($escalated->orderItem->fresh()->refunded_at);
        $this->assertSame('admin_reviewed', $escalated->auditLogs()->get()->last()->step);

        $this->postJson("/api/admin/refund-requests/{$escalated->id}/review", ['decision' => 'denied'])
            ->assertUnprocessable();
    }

    public function test_only_approve_or_deny_are_valid_review_decisions(): void
    {
        $escalated = $this->escalatedRequest();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/admin/refund-requests/{$escalated->id}/review", ['decision' => 'escalated'])
            ->assertJsonValidationErrors('decision');
    }
}
