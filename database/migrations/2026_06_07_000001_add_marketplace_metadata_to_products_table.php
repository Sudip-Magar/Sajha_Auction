<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('is_approved');
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->unsignedBigInteger('views_count')->default(0)->after('is_trending');
            $table->string('location')->nullable()->after('views_count');
            $table->boolean('delivery_available')->default(false)->after('location');
            $table->timestamp('expires_at')->nullable()->after('delivery_available');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'is_featured',
                'is_trending',
                'views_count',
                'location',
                'delivery_available',
                'expires_at',
            ]);
        });
    }
};
