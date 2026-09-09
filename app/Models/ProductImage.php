<?php

namespace App\Models;

use App\Enums\ProductImageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'image_type',
        'sort_order',
    ];

    protected $casts = [
        'image_type' => ProductImageType::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
