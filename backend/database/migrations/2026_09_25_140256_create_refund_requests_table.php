<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('submitted_email');
            $table->string('submitted_order_number', 50);
            $table->text('message');
            $table->string('ai_reason', 30)->nullable();
            $table->decimal('ai_confidence', 3, 2)->nullable();
            $table->json('ai_flags')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('ai_provider', 30)->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('decision', 20)->index();
            $table->json('matched_rules');
            $table->text('customer_reply');
            $table->string('final_decision', 20)->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
