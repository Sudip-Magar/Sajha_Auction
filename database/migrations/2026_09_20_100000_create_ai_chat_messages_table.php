<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 64)->nullable()->comment('Owner key for guests (user_id is null)');
            $table->string('role', 16)->comment('user or assistant');
            $table->text('body');
            $table->boolean('is_error')->default(false)->comment('Assistant fallback text, excluded from model history');
            $table->timestamps();

            $table->index(['user_id', 'id']);
            $table->index(['session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
