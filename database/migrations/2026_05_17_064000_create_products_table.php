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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('catefories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('specifications')->nullable(); // JSON field for detailed specs
            $table->string('condition')->default('new'); // 'new', 'like-new', 'used'
            $table->decimal('retail_price', 15, 2); // Original manufacturer price (for reference)
            $table->decimal('sale_price', 15, 2)->nullable(); // Direct sell price (ecommerce mode)
            $table->integer('stock_quantity')->default(0); // For ecommerce products
            $table->string('auction_type')->nullable()->comment('penny, traditional');
            $table->decimal('starting_bid', 15, 2)->nullable(); // First bid price (auction mode)
            $table->decimal('starting_price_cents', 10, 0)->default(0); // Penny auction: starts at 0 cents
            $table->integer('bid_increment_cents')->default(1); // How much each bid raises price (1 cent)
            $table->integer('timer_seconds')->default(60); // Initial countdown timer
            $table->integer('timer_extension_seconds')->default(15); // How much timer extends per bid
            $table->dateTime('auction_start')->nullable(); // When auction opens
            $table->dateTime('auction_end')->nullable(); // When auction closes (for traditional)
            $table->dateTime('scheduled_for')->nullable(); // Future auction scheduling
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
