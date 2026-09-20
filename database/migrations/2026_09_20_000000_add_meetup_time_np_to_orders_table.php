<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('meetup_time_np')->nullable()->after('meetup_time')->comment('Preferred meetup date (B.S.) and time, e.g. 2083-05-12 14:30');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('meetup_time_np');
        });
    }
};
