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
            $table->string('cancellation_reason_category')->nullable()->after('notes')
                ->comment('defect_mismatch, changed_mind, other - buyer-given reason, null for seller cancellations');
            $table->text('cancellation_note')->nullable()->after('cancellation_reason_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason_category', 'cancellation_note']);
        });
    }
};
