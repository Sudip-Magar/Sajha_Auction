<?php

namespace App\Models;

use App\Enums\ProductAuctionType;
use App\Enums\ProductNegotiability;
use App\Enums\ProductSaleType;
use App\Services\AuctionValuationService;
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
        'sub_category_id',
        'name',
        'slug',
        'description',
        'specifications',
        'condition',
        'usage_duration',
        'purchase_date',
        'quantity',
        'retail_price',
        'sale_price',
        'negotiable',
        'listing_type',
        'is_approved',
        'is_featured',
        'is_trending',
        'views_count',
        'location',
        'meetup_location',
        'meetup_instructions',
        'delivery_available',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'listing_type' => ProductSaleType::class,
        'negotiable' => ProductNegotiability::class,
        'purchase_date' => 'date',
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
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
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

    public function wishlistedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(ProductTimeline::class)->orderBy('created_at', 'asc');
    }

    public function isDirectSell(): bool
    {
        return $this->listing_type === ProductSaleType::DIRECT_SELLER;
    }

    public function isAuction(): bool
    {
        return $this->listing_type === ProductSaleType::AUCTION;
    }

    public function isWishlistedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return Wishlist::where('user_id', $user->id)
            ->where('product_id', $this->id)
            ->exists();
    }

    public function logTimeline(string $eventType, string $title, ?string $description = null, ?User $actor = null): ProductTimeline
    {
        return $this->timelines()->create([
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'actor_id' => $actor?->id,
        ]);
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

        $this->logTimeline(
            'approved',
            'Product Approved',
            'Product listing approved by admin and published live on the marketplace.'
        );
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

    /**
     * System-estimated reference value based on original price, age and
     * condition. Null when required valuation inputs aren't available —
     * never overwrites or substitutes retail_price (the original price).
     */
    public function getEstimatedValueAttribute(): ?float
    {
        return AuctionValuationService::calculateEstimatedValue(
            $this->retail_price !== null ? (float) $this->retail_price : null,
            $this->purchase_date,
            $this->condition
        );
    }

    public function getSuggestedStartingPriceAttribute(): ?float
    {
        return AuctionValuationService::calculateSuggestedStartingPrice($this->estimated_value);
    }
}
