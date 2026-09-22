<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('transaction_uuid')->nullable()->unique();
            $table->string('status')->default('pending'); // pending | paid | expired
            $table->timestamp('verdict_recorded_at');
            $table->timestamp('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->boolean('legal_action_flagged')->default(false);

            // The seller's access flags immediately before revocation, so
            // restoring access after payment puts back exactly what they had
            // rather than unconditionally granting both.
            $table->boolean('prior_is_auction_allowed')->default(false);
            $table->boolean('prior_is_seller')->default(false);

            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('seller_damage_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('damage_penalty_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('seller_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_permanently_banned')->default(false)->after('is_auction_allowed');
            $table->string('permanent_ban_reason')->nullable()->after('is_permanently_banned');
            $table->timestamp('permanently_banned_at')->nullable()->after('permanent_ban_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_permanently_banned', 'permanent_ban_reason', 'permanently_banned_at']);
        });
        Schema::dropIfExists('seller_damage_strikes');
        Schema::dropIfExists('damage_penalties');
    }
};
