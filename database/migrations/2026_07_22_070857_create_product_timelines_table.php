<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_timelines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('event_type')->comment('created, uploaded, approved, updated, added_to_cart, ordered, meetup_scheduled, completed, sold, cancelled');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_timelines');
    }
};
