<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('auction_type');
            $table->unsignedInteger('quantity')->default(1);
            $table->dateTime('starts_at_en');
            $table->string('starts_at_np');
            $table->dateTime('ends_at_en')->nullable();
            $table->string('ends_at_np')->nullable();
            $table->decimal('starting_bid', 15, 2)->nullable();
            $table->decimal('current_bid', 15, 2)->nullable();
            $table->unsignedInteger('starting_price_cents')->nullable();
            $table->unsignedInteger('current_price_cents')->nullable();
            $table->unsignedInteger('bid_increment_cents')->nullable();
            $table->unsignedInteger('timer_seconds')->nullable();
            $table->unsignedInteger('timer_extension_seconds')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
