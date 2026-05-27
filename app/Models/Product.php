<?php

namespace App\Models;

use App\Enums\ProductAuctionType;
use App\Enums\ProductSaleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
        'type',
        'is_approved',
        'status',
    ];

    protected $casts = [
        'type' => ProductSaleType::class,
        'is_approved' => 'boolean',
        'specifications' => 'string',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Product $product): void {
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

    public function directSellerProduct(): HasOne
    {
        return $this->hasOne(DirectSellerProduct::class);
    }

    public function pennyAuction(): HasOne
    {
        return $this->hasOne(PennyAuction::class);
    }

    public function traditionalAuction(): HasOne
    {
        return $this->hasOne(TraditionalAuction::class);
    }

    public function auction(): HasOne
    {
        return $this->hasOne(Auction::class);
    }

    public function approveListing(): void
    {
        $this->update([
            'is_approved' => true,
            'status' => 'active',
        ]);

        if ($this->type !== ProductSaleType::AUCTION || $this->auction) {
            return;
        }

        $auctionType = $this->auction_type;

        if ($auctionType === ProductAuctionType::PENNY && $this->pennyAuction) {
            $this->auction()->create([
                'auction_type' => ProductAuctionType::PENNY->value,
                'quantity' => $this->pennyAuction->quantity,
                'starts_at_en' => $this->pennyAuction->auction_start_en,
                'starts_at_np' => $this->pennyAuction->auction_start_np,
                'starting_price_cents' => $this->pennyAuction->starting_price_cents,
                'current_price_cents' => $this->pennyAuction->starting_price_cents,
                'bid_increment_cents' => $this->pennyAuction->bid_increment_cents,
                'timer_seconds' => $this->pennyAuction->timer_seconds,
                'timer_extension_seconds' => $this->pennyAuction->timer_extension_seconds,
            ]);

            return;
        }

        if ($auctionType === ProductAuctionType::TRADITIONAL && $this->traditionalAuction) {
            $this->auction()->create([
                'auction_type' => ProductAuctionType::TRADITIONAL->value,
                'quantity' => $this->traditionalAuction->quantity,
                'starts_at_en' => $this->traditionalAuction->auction_start_en,
                'starts_at_np' => $this->traditionalAuction->auction_start_np,
                'ends_at_en' => $this->traditionalAuction->auction_end_en,
                'ends_at_np' => $this->traditionalAuction->auction_end_np,
                'starting_bid' => $this->traditionalAuction->starting_bid,
                'current_bid' => $this->traditionalAuction->starting_bid,
            ]);
        }
    }

    public function getImageAttribute(): ?string
    {
        return $this->images->first()?->path;
    }

    public function getAuctionTypeAttribute(): ?ProductAuctionType
    {
        if ($this->pennyAuction) {
            return ProductAuctionType::PENNY;
        }

        if ($this->traditionalAuction) {
            return ProductAuctionType::TRADITIONAL;
        }

        if ($this->auction?->auction_type) {
            return ProductAuctionType::tryFrom($this->auction->auction_type);
        }

        return null;
    }

    public function getSalePriceAttribute(): ?float
    {
        return $this->directSellerProduct?->price !== null
            ? (float) $this->directSellerProduct->price
            : null;
    }

    public function getStockQuantityAttribute(): int
    {
        return (int) (
            $this->directSellerProduct?->quantity
            ?? $this->traditionalAuction?->quantity
            ?? $this->pennyAuction?->quantity
            ?? $this->auction?->quantity
            ?? 0
        );
    }

    public function getStartingBidAttribute(): ?float
    {
        return $this->traditionalAuction?->starting_bid !== null
            ? (float) $this->traditionalAuction->starting_bid
            : null;
    }

    public function getStartingPriceCentsAttribute(): int
    {
        return (int) ($this->pennyAuction?->starting_price_cents ?? 0);
    }

    public function getBidIncrementCentsAttribute(): int
    {
        return (int) ($this->pennyAuction?->bid_increment_cents ?? 1);
    }

    public function getTimerSecondsAttribute(): int
    {
        return (int) ($this->pennyAuction?->timer_seconds ?? 60);
    }

    public function getTimerExtensionSecondsAttribute(): int
    {
        return (int) ($this->pennyAuction?->timer_extension_seconds ?? 15);
    }

    public function getAuctionStartEnAttribute(): ?Carbon
    {
        return $this->traditionalAuction?->auction_start_en ?? $this->pennyAuction?->auction_start_en;
    }

    public function getAuctionStartNpAttribute(): ?string
    {
        return $this->traditionalAuction?->auction_start_np ?? $this->pennyAuction?->auction_start_np;
    }

    public function getAuctionEndEnAttribute(): ?Carbon
    {
        return $this->traditionalAuction?->auction_end_en;
    }

    public function getAuctionEndNpAttribute(): ?string
    {
        return $this->traditionalAuction?->auction_end_np;
    }
}
