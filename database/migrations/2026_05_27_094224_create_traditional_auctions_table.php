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
        Schema::create('traditional_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained('auctions')->cascadeOnDelete();
            $table->decimal('starting_bid', 12, 2);
            $table->decimal('reserve_price', 12, 2)->nullable()->comment('Minimum acceptable price; NULL means no reserve');
            $table->decimal('min_bid_increment', 12, 2)->comment('Minimum amount each bid must exceed the last');
            $table->unsignedInteger('timer_start_seconds')->comment('Initial countdown duration in seconds');
            $table->unsignedInteger('timer_reset_seconds')->comment('Countdown resets to this value after each bid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traditional_auctions');
    }
};
