<?php

namespace App\Models;

use App\Enums\DocumentImageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentImage extends Model
{
    public $fillable = [
        'user_id',
        'type',
        'is_approved',
        'is_rejected',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentImageType::class,
            'is_approved' => 'boolean',
            'is_rejected' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
