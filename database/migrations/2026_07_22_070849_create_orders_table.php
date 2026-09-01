<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending')->comment('pending, confirmed, meetup_scheduled, completed, cancelled');
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_method')->default('cash_on_meetup')->comment('cash_on_meetup, cash_on_delivery, khalti, esewa, wallet');
            $table->string('payment_status')->default('pending')->comment('pending, paid, refunded');
            $table->string('handover_type')->default('meetup')->comment('meetup, delivery');
            $table->string('meetup_location')->nullable();
            $table->dateTime('meetup_time')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
