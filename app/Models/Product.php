<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'specifications',
        'condition',
        'retail_price',
        'sale_price',
        'stock_quantity',
        'auction_type',
        'starting_bid',
        'starting_price_cents',
        'bid_increment_cents',
        'timer_seconds',
        'timer_extension_seconds',
        'auction_start_en',
        'auction_start_np',
        'auction_end_en',
        'auction_end_np',
        'scheduled_for',
        'type',
        'is_approved',
        'status',
    ];

    protected $casts = [
        'auction_start_en' => 'datetime',
        'auction_end_en' => 'datetime',
        'scheduled_for' => 'datetime',
        'is_approved' => 'boolean',
        'specifications' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $product->slug = Str::slug($product->name).'-'.Str::random(5);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getImageAttribute(): ?string
    {
        return $this->images->first()?->path;
    }
}
