<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Estimated-value / depreciation valuation for auction products.
 *
 * Kept as a set of pure, stateless calculations (mirroring AuctionEngineService)
 * so the depreciation table, condition factors and starting-price ratio can
 * later be swapped for per-category rules or admin-configurable settings
 * without touching any caller.
 */
class AuctionValuationService
{
    /**
     * Deterministic depreciation rate by product age in months.
     */
    public static function depreciationRateForAge(int $ageInMonths): float
    {
        return match (true) {
            $ageInMonths < 6 => 0.10,
            $ageInMonths < 12 => 0.18,
            $ageInMonths < 24 => 0.30,
            $ageInMonths < 36 => 0.45,
            $ageInMonths < 48 => 0.60,
            $ageInMonths < 60 => 0.70,
            default => 0.75,
        };
    }

    /**
     * Maps the marketplace's free-text `products.condition` values onto the
     * Excellent/Good/Fair/Poor valuation adjustment. Unrecognized values fall
     * back to the neutral Good (1.00) factor rather than inventing a value.
     */
    public static function conditionFactor(?string $condition): float
    {
        return match ($condition) {
            'new', 'like-new' => 1.05,
            'lightly-used', 'refurbished' => 1.00,
            'well-used', 'used' => 0.90,
            'poor', 'heavily-used' => 0.80,
            default => 1.00,
        };
    }

    /**
     * estimated_value = (original_price * (1 - depreciation_rate)) * condition_factor
     *
     * Returns null (never a guessed value) when the required inputs
     * (a positive original price and a non-future purchase date) aren't
     * available, per the product's edge-case handling.
     */
    public static function calculateEstimatedValue(?float $originalPrice, ?Carbon $purchaseDate, ?string $condition): ?float
    {
        if ($originalPrice === null || $originalPrice <= 0) {
            return null;
        }

        if ($purchaseDate === null || $purchaseDate->isFuture()) {
            return null;
        }

        $ageInMonths = max(0, (int) $purchaseDate->diffInMonths(now()));
        $baseEstimatedValue = $originalPrice * (1 - static::depreciationRateForAge($ageInMonths));
        $estimatedValue = $baseEstimatedValue * static::conditionFactor($condition);

        return round(max(0.0, $estimatedValue), 2);
    }

    /**
     * suggested_starting_price = estimated_value * ratio (default 80%).
     * This is a reference suggestion only — never the enforced starting_price.
     */
    public static function calculateSuggestedStartingPrice(?float $estimatedValue, float $ratio = 0.80): ?float
    {
        if ($estimatedValue === null || $estimatedValue <= 0) {
            return null;
        }

        return round($estimatedValue * $ratio, 2);
    }
}
