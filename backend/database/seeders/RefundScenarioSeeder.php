<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\RefundDecision;
use App\Enums\RefundReason;
use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use Illuminate\Database\Seeder;

/**
 * 15 synthetic customers. Each of the first 14 is set up to exercise one policy
 * rule; the last is an ordinary customer with no special setup.
 * Dates are relative to "now" so the scenarios stay valid whenever the app is run.
 */
class RefundScenarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->customers() as $data) {
            $customer = Customer::create($data['customer']);

            foreach ($data['orders'] as $orderData) {
                $this->createOrder($customer, $orderData);
            }
        }

        $this->seedPreviouslyDeniedRequest();
    }

    /**
     * @param  array{number: string, status: OrderStatus, delivered_days_ago: ?int, items: list<array<string, mixed>>}  $data
     */
    private function createOrder(Customer $customer, array $data): void
    {
        $deliveredAt = $data['delivered_days_ago'] !== null ? now()->subDays($data['delivered_days_ago']) : null;

        $order = $customer->orders()->create([
            'order_number' => $data['number'],
            'status' => $data['status'],
            'ordered_at' => ($deliveredAt ?? now())->copy()->subDays(4),
            'delivered_at' => $deliveredAt,
            'total' => collect($data['items'])->sum(fn (array $item) => $item['unit_price'] * ($item['quantity'] ?? 1)),
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_name' => $item['product_name'],
                'category' => $item['category'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'] ?? 1,
                'is_final_sale' => $item['is_final_sale'] ?? false,
                'refunded_at' => isset($item['refunded_days_ago']) ? now()->subDays($item['refunded_days_ago']) : null,
            ]);
        }
    }

    private function seedPreviouslyDeniedRequest(): void
    {
        $order = Order::where('order_number', 'ORD-10015')->with('items', 'customer')->firstOrFail();
        $item = $order->items->first();

        $request = RefundRequest::create([
            'reference' => 'RF-SEED0001',
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'submitted_email' => $order->customer->email,
            'submitted_order_number' => $order->order_number,
            'message' => 'The kettle leaks from the base, I want my money back.',
            'ai_reason' => RefundReason::Damaged,
            'ai_confidence' => 0.55,
            'ai_flags' => [],
            'ai_summary' => 'Customer reports the kettle leaks from the base.',
            'ai_provider' => 'seed',
            'refund_amount' => $item->lineTotal(),
            'decision' => RefundDecision::Escalated,
            'matched_rules' => ['R8'],
            'customer_reply' => 'Thanks for your request. It needs a quick review by our support team.',
            'final_decision' => RefundDecision::Denied,
        ]);

        $request->forceFill([
            'reviewed_at' => now()->subDays(3),
            'review_note' => 'Photos provided showed no leak or damage; kettle tested fine.',
            'created_at' => now()->subDays(4),
        ])->save();

        $request->auditLogs()->create([
            'step' => 'admin_reviewed',
            'payload' => ['decision' => 'denied', 'reviewer' => 'seed', 'note' => $request->review_note],
        ]);
    }

    /**
     * @return list<array{customer: array<string, string>, orders: list<array<string, mixed>>}>
     */
    private function customers(): array
    {
        $delivered = OrderStatus::Delivered;

        return [
            // R9 approved: damaged item within the window.
            $this->customer('Ava', 'Thompson', '+1 415 555 0101', [
                ['number' => 'ORD-10001', 'status' => $delivered, 'delivered_days_ago' => 6, 'items' => [
                    ['product_name' => 'Wireless Noise-Cancelling Headphones', 'category' => 'electronics', 'unit_price' => 89.99],
                ]],
            ]),
            // R9 approved: wrong item received.
            $this->customer('Liam', 'Carter', '+1 415 555 0102', [
                ['number' => 'ORD-10002', 'status' => $delivered, 'delivered_days_ago' => 4, 'items' => [
                    ['product_name' => 'Trail Running Shoes (Size 10)', 'category' => 'footwear', 'unit_price' => 74.50],
                ]],
            ]),
            // R9 approved: change of mind within the window.
            $this->customer('Sophia', 'Nguyen', '+1 415 555 0103', [
                ['number' => 'ORD-10003', 'status' => $delivered, 'delivered_days_ago' => 10, 'items' => [
                    ['product_name' => 'Ceramic Table Lamp', 'category' => 'home', 'unit_price' => 59.00],
                ]],
            ]),
            // R4 denied: final sale, change of mind.
            $this->customer('Noah', 'Patel', '+1 415 555 0104', [
                ['number' => 'ORD-10004', 'status' => $delivered, 'delivered_days_ago' => 7, 'items' => [
                    ['product_name' => 'Clearance Denim Jacket', 'category' => 'apparel', 'unit_price' => 39.00, 'is_final_sale' => true],
                ]],
            ]),
            // R4a escalated: final sale item arrived damaged.
            $this->customer('Mia', 'Rodriguez', '+1 415 555 0105', [
                ['number' => 'ORD-10005', 'status' => $delivered, 'delivered_days_ago' => 3, 'items' => [
                    ['product_name' => 'Hand-Blown Glass Vase', 'category' => 'home', 'unit_price' => 45.00, 'is_final_sale' => true],
                ]],
            ]),
            // R5 denied: delivered 45 days ago.
            $this->customer('Ethan', 'Brooks', '+1 415 555 0106', [
                ['number' => 'ORD-10006', 'status' => $delivered, 'delivered_days_ago' => 45, 'items' => [
                    ['product_name' => 'Portable Bluetooth Speaker', 'category' => 'electronics', 'unit_price' => 65.00],
                ]],
            ]),
            // R6 escalated: refund above $500.
            $this->customer('Olivia', 'Kim', '+1 415 555 0107', [
                ['number' => 'ORD-10007', 'status' => $delivered, 'delivered_days_ago' => 5, 'items' => [
                    ['product_name' => 'UltraBook Pro 14 Laptop', 'category' => 'electronics', 'unit_price' => 1299.00],
                ]],
            ]),
            // R3 denied: order not delivered yet.
            $this->customer('Lucas', 'Martin', '+1 415 555 0108', [
                ['number' => 'ORD-10008', 'status' => OrderStatus::Shipped, 'delivered_days_ago' => null, 'items' => [
                    ['product_name' => 'Fitness Smart Watch', 'category' => 'electronics', 'unit_price' => 199.00],
                ]],
            ]),
            // R2 denied: item already refunded.
            $this->customer('Isabella', 'Chen', '+1 415 555 0109', [
                ['number' => 'ORD-10009', 'status' => $delivered, 'delivered_days_ago' => 12, 'items' => [
                    ['product_name' => 'Cork Yoga Mat', 'category' => 'sports', 'unit_price' => 35.00, 'refunded_days_ago' => 5],
                ]],
            ]),
            // R7 escalated: three refunds in the last 90 days.
            $this->customer('James', 'Wilson', '+1 415 555 0110', [
                ['number' => 'ORD-10010', 'status' => $delivered, 'delivered_days_ago' => 8, 'items' => [
                    ['product_name' => 'Wireless Gaming Mouse', 'category' => 'electronics', 'unit_price' => 49.00],
                ]],
                ['number' => 'ORD-10011', 'status' => $delivered, 'delivered_days_ago' => 60, 'items' => [
                    ['product_name' => 'Mechanical Keyboard', 'category' => 'electronics', 'unit_price' => 89.00, 'refunded_days_ago' => 55],
                    ['product_name' => 'USB-C Hub', 'category' => 'electronics', 'unit_price' => 39.00, 'refunded_days_ago' => 40],
                    ['product_name' => 'Laptop Stand', 'category' => 'electronics', 'unit_price' => 29.00, 'refunded_days_ago' => 20],
                ]],
            ]),
            // R7 escalated: prompt-injection attempt (scenario is in the message).
            $this->customer('Amelia', 'Scott', '+1 415 555 0111', [
                ['number' => 'ORD-10012', 'status' => $delivered, 'delivered_days_ago' => 5, 'items' => [
                    ['product_name' => 'Leather Bifold Wallet', 'category' => 'accessories', 'unit_price' => 55.00],
                ]],
            ]),
            // R7 escalated: claims not received but order is marked delivered.
            $this->customer('Benjamin', 'Lee', '+1 415 555 0112', [
                ['number' => 'ORD-10013', 'status' => $delivered, 'delivered_days_ago' => 2, 'items' => [
                    ['product_name' => 'Burr Coffee Grinder', 'category' => 'kitchen', 'unit_price' => 85.00],
                ]],
            ]),
            // R8 escalated: multi-item order and a vague message.
            $this->customer('Charlotte', 'Davis', '+1 415 555 0113', [
                ['number' => 'ORD-10014', 'status' => $delivered, 'delivered_days_ago' => 6, 'items' => [
                    ['product_name' => 'Organic Cotton T-Shirt', 'category' => 'apparel', 'unit_price' => 25.00],
                    ['product_name' => 'Scented Candle Set', 'category' => 'home', 'unit_price' => 32.00],
                    ['product_name' => 'Linen Throw Pillow', 'category' => 'home', 'unit_price' => 28.00],
                ]],
            ]),
            // R7 escalated: same item was previously reviewed and denied.
            $this->customer('Henry', 'Adams', '+1 415 555 0114', [
                ['number' => 'ORD-10015', 'status' => $delivered, 'delivered_days_ago' => 9, 'items' => [
                    ['product_name' => 'Stainless Steel Electric Kettle', 'category' => 'kitchen', 'unit_price' => 42.00],
                ]],
            ]),
            // Control customer: ordinary history, no special setup.
            $this->customer('Grace', 'Miller', '+1 415 555 0115', [
                ['number' => 'ORD-10016', 'status' => $delivered, 'delivered_days_ago' => 20, 'items' => [
                    ['product_name' => 'Linen Bedsheet Set (Queen)', 'category' => 'home', 'unit_price' => 120.00],
                ]],
                ['number' => 'ORD-10017', 'status' => OrderStatus::Processing, 'delivered_days_ago' => null, 'items' => [
                    ['product_name' => 'Bamboo Bath Towels (Set of 4)', 'category' => 'home', 'unit_price' => 48.00],
                ]],
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $orders
     * @return array{customer: array<string, string>, orders: list<array<string, mixed>>}
     */
    private function customer(string $firstName, string $lastName, string $phone, array $orders): array
    {
        return [
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower("{$firstName}.{$lastName}@example.com"),
                'phone' => $phone,
            ],
            'orders' => $orders,
        ];
    }
}
