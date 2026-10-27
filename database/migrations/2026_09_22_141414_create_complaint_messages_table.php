<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Tags who sent the message; sender_id is admins.id or users.id
            // depending on sender_role, same loose "party" pattern already
            // used by payment_transactions.party (no single FK possible
            // since it points at two different tables).
            $table->string('sender_role');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_messages');
    }
};
