<?php

use App\Enums\StatusState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update([
                'status' => StatusState::ACTIVE->value,
            ]);

        DB::table('users')
            ->where('status', 'ACTIVE')
            ->update([
                'status' => StatusState::ACTIVE->value,
            ]);

        DB::table('users')
            ->where('status', 'INACTIVE')
            ->update([
                'status' => StatusState::INACTIVE->value,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('status', StatusState::ACTIVE->value)
            ->update([
                'status' => 'ACTIVE',
            ]);

        DB::table('users')
            ->where('status', StatusState::INACTIVE->value)
            ->update([
                'status' => 'INACTIVE',
            ]);
    }
};
