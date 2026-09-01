<?php

use App\Enums\DocumentImageType;
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
        Schema::create('document_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', array_map(
                fn (DocumentImageType $type): string => $type->value,
                DocumentImageType::cases()
            ))->default(DocumentImageType::CITIZENSHIPFRONT->value);
            $table->boolean('is_rejected')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_images');
    }
};
