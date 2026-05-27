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
        Schema::create('penny_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('starting_price_cents')->default(0);
            $table->unsignedInteger('bid_increment_cents')->default(1);
            $table->unsignedInteger('timer_seconds')->default(60);
            $table->unsignedInteger('timer_extension_seconds')->default(15);
            $table->dateTime('auction_start_en');
            $table->string('auction_start_np');
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
