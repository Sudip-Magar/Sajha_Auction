<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penny_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->unsignedInteger('bid_cost_credits')->comment('Credits consumed per bid placed by a user');
            $table->decimal('price_increment', 12, 2)->comment('How much the current price rises per bid (e.g. 1.00 NPR)');
            $table->unsignedInteger('timer_start_seconds')->comment('Initial countdown duration in seconds');
            $table->unsignedInteger('timer_reset_seconds')->comment('Countdown resets to this value after each bid');

            // Optional fair-play rules
            $table->unsignedInteger('max_bids_per_user')->nullable()->comment('Optional cap to prevent one user dominating');
            $table->boolean('credit_refund_on_loss')->default(false)->comment('Whether losing bidders get credits refunded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penny_auctions');
    }
};
