<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('meetup_location')->nullable()->after('location')->comment('Specific meetup place/location for direct sale');
            $table->text('meetup_instructions')->nullable()->after('meetup_location')->comment('Instructions or availability for meetup');
            $table->string('usage_duration')->nullable()->after('condition')->comment('How long the second hand product was used');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['meetup_location', 'meetup_instructions', 'usage_duration']);
        });
    }
};
