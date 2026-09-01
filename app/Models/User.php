<?php

namespace App\Models;

use App\Enums\StatusState;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'username', 'google_id', 'avatar', 'phone', 'date_of_birth_en', 'daate_of_birth_np', 'gender', 'bio', 'is_verified', 'is_seller', 'is_auction_allowed', 'seller_application_pending', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_seller' => 'boolean',
            'is_auction_allowed' => 'boolean',
            'seller_application_pending' => 'boolean',
        ];
    }

    public function documentImages(): HasMany
    {
        return $this->hasMany(DocumentImage::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bookmarks')->withTimestamps();
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function buyerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function sellerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function isActiveStatus(): bool
    {
        return $this->status === StatusState::ACTIVE->value;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereRaw('LOWER(status) = ?', [StatusState::ACTIVE->value]);
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => strtolower($value ?? StatusState::ACTIVE->value),
            set: fn (?string $value): string => strtolower($value ?? StatusState::ACTIVE->value),
        );
    }
}
