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
        Schema::create('traditional_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('starting_bid', 15, 2);
            $table->decimal('bid_increment', 15, 2)->default(1);
            $table->dateTime('auction_start_en');
            $table->string('auction_start_np');
            $table->dateTime('auction_end_en');
            $table->string('auction_end_np');
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
