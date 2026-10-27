<?php

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('approval_status')->default(ProductApprovalStatus::PENDING->value)->after('is_approved');
            $table->string('remarks')->nullable()->after('approval_status');
        });

        // Backfill from the boolean this column replaces: true -> APPROVED,
        // false -> PENDING. There is no reliable signal in is_approved alone
        // to distinguish a never-reviewed listing from a rejected one, so
        // false rows land on PENDING (re-review), not REJECTED.
        DB::table('products')->where('is_approved', true)->update(['approval_status' => ProductApprovalStatus::APPROVED->value]);
        DB::table('products')->where('is_approved', false)->update(['approval_status' => ProductApprovalStatus::PENDING->value]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('negotiable');
        });

        DB::table('products')->where('approval_status', ProductApprovalStatus::APPROVED->value)->update(['is_approved' => true]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'remarks']);
        });
    }
};
