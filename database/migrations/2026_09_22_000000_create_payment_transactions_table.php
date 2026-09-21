<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // deposit_paid, balance_paid_cash, refund_issued, deposit_forfeited,
            // payout_sent, debt_recorded, debt_recovered
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable(); // esewa | cash
            $table->string('status')->default('pending'); // pending | completed | failed
            $table->string('reference')->nullable()->unique(); // eSewa transaction_uuid for online payments
            $table->string('party')->nullable(); // admin | seller | buyer: who the money goes to / comes from
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type']);
        });

        Schema::create('seller_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('related_order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('recovered_amount', 12, 2)->default(0);
            $table->string('reason');
            $table->string('status')->default('outstanding'); // outstanding | recovered
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_role'); // buyer | seller
            $table->string('purpose'); // seller_forfeit_share | buyer_refund
            $table->decimal('amount', 12, 2);
            $table->decimal('debt_deducted', 12, 2)->default(0);
            $table->string('esewa_name')->nullable();
            $table->string('esewa_phone')->nullable();
            $table->string('qr_image_path')->nullable();
            $table->string('payout_status')->default('awaiting_details'); // awaiting_details | pending | sent
            $table->timestamp('details_submitted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'payout_status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('complaint_status')->nullable()->after('cancellation_note'); // under_review | upheld | rejected
            $table->text('complaint_resolution_note')->nullable()->after('complaint_status');
            $table->timestamp('stale_notified_at')->nullable()->after('complaint_resolution_note');
        });

        // Orders paid before the ledger existed get one deposit_paid row so their totals stay correct.
        DB::table('orders')
            ->whereIn('deposit_status', ['paid', 'refund_owed', 'forfeited'])
            ->where('deposit_amount', '>', 0)
            ->orderBy('id')
            ->each(function (object $order): void {
                DB::table('payment_transactions')->insert([
                    'order_id' => $order->id,
                    'type' => 'deposit_paid',
                    'amount' => $order->deposit_amount,
                    'payment_method' => 'esewa',
                    'status' => 'completed',
                    'party' => 'admin',
                    'notes' => 'Backfilled from the pre-ledger deposit record.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['complaint_status', 'complaint_resolution_note', 'stale_notified_at']);
        });
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('seller_debts');
        Schema::dropIfExists('payment_transactions');
    }
};
