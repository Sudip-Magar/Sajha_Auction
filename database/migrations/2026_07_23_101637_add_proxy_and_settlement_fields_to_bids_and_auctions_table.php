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
        Schema::table('bids', function (Blueprint $table) {
            $table->decimal('max_proxy_amount', 12, 2)->nullable()->after('bid_amount');
            $table->boolean('is_proxy')->default(false)->after('max_proxy_amount');
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->decimal('winning_price', 12, 2)->nullable()->after('current_price');
            $table->text('settlement_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['max_proxy_amount', 'is_proxy']);
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['winning_price', 'settlement_reason']);
        });
    }
};
