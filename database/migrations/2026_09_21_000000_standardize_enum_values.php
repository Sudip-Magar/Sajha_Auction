<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GenderState was stored UPPER (MALE); every other enum is lowercase.
        DB::table('users')->whereNotNull('gender')->update(['gender' => DB::raw('LOWER(gender)')]);
        DB::table('admins')->whereNotNull('gender')->update(['gender' => DB::raw('LOWER(gender)')]);

        // DocumentImageType used 'citizenship Front' / 'citizenship Back' (mixed case, with a space)
        // and the column was a hard MySQL ENUM, so it is loosened to a string first.
        Schema::table('document_images', function (Blueprint $table): void {
            $table->string('type')->default('citizenship_front')->change();
        });

        DB::table('document_images')->where('type', 'citizenship Front')->update(['type' => 'citizenship_front']);
        DB::table('document_images')->where('type', 'citizenship Back')->update(['type' => 'citizenship_back']);
    }

    public function down(): void
    {
        DB::table('document_images')->where('type', 'citizenship_front')->update(['type' => 'citizenship Front']);
        DB::table('document_images')->where('type', 'citizenship_back')->update(['type' => 'citizenship Back']);

        DB::table('users')->whereNotNull('gender')->update(['gender' => DB::raw('UPPER(gender)')]);
        DB::table('admins')->whereNotNull('gender')->update(['gender' => DB::raw('UPPER(gender)')]);
    }
};
