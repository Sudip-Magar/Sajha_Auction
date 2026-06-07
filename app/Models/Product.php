<?php

namespace App\Models;

use App\Enums\ProductAuctionType;
use App\Enums\ProductSaleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'specifications',
        'condition',
        'quantity',
        'retail_price',
        'sale_price',
        'listing_type',
        'is_approved',
        'is_featured',
        'is_trending',
        'views_count',
        'location',
        'delivery_available',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'listing_type' => ProductSaleType::class,
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'views_count' => 'integer',
        'delivery_available' => 'boolean',
        'expires_at' => 'datetime',
        'specifications' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Product $product): void {
            $product->slug = Str::slug($product->name).'-'.Str::random(5);
            if (empty($product->sku)) {
                $product->sku = 'PRD-'.strtoupper(Str::random(8));
            }
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

    public function auction(): HasOne
    {
        return $this->hasOne(Auction::class);
    }

    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    public function approveListing(): void
    {
        $this->update([
            'is_approved' => true,
            'expires_at' => $this->expires_at ?? now()->addDays(30),
            'status' => 'active',
        ]);

        if ($this->auction) {
            $this->auction->update(['status' => 'active']);
        }
    }

    public function getImageAttribute(): ?string
    {
        return $this->images->first()?->path;
    }

    public function getAuctionTypeAttribute(): ?ProductAuctionType
    {
        if ($this->auction?->auction_type) {
            return ProductAuctionType::tryFrom($this->auction->auction_type);
        }

        return null;
    }

    public function getStartingBidAttribute(): ?float
    {
        return $this->auction?->traditionalAuction?->starting_bid !== null
            ? (float) $this->auction->traditionalAuction->starting_bid
            : null;
    }

    public function getAuctionStartEnAttribute(): ?Carbon
    {
        return $this->auction?->start_time;
    }

    public function getAuctionStartNpAttribute(): ?string
    {
        return $this->auction?->start_time_np;
    }

    public function getAuctionEndEnAttribute(): ?Carbon
    {
        return $this->auction?->end_time;
    }

    public function getAuctionEndNpAttribute(): ?string
    {
        return $this->auction?->end_time_np;
    }

    public function getStockQuantityAttribute(): int
    {
        return (int) ($this->quantity ?? 0);
    }
}
