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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('auction_id')->nullable()->after('seller_id')->constrained('auctions')->nullOnDelete();
            $table->decimal('deposit_amount', 15, 2)->nullable()->after('total_amount');
            $table->string('deposit_status')->default('not_required')->after('deposit_amount')
                ->comment('not_required, pending, paid, failed');
            $table->string('deposit_transaction_uuid')->nullable()->unique()->after('deposit_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auction_id');
            $table->dropColumn(['deposit_amount', 'deposit_status', 'deposit_transaction_uuid']);
        });
    }
};
