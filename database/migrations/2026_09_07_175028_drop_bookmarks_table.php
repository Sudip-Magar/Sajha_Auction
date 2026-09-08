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
        // Bookmarks duplicated the `wishlists` table exactly (same columns,
        // same unique constraint) and every write to it was a side effect of
        // Home::toggleWishlist(), which always wrote the identical row to
        // `wishlists` in the same call — so no data is lost by dropping this.
        Schema::dropIfExists('bookmarks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }
};
