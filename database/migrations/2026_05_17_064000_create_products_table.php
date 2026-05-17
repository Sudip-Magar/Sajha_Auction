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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 15, 2)->nullable(); // For direct sell
            $table->decimal('starting_bid', 15, 2)->nullable(); // For auction
            $table->dateTime('auction_end')->nullable();
            $table->string('type'); // 'auction' or 'sell'
            $table->string('image')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('status')->default('pending'); // 'pending', 'active', 'sold', 'expired'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
