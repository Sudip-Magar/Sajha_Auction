<?php

namespace App\Services;

use App\Enums\DamagePenaltyStatus;
use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Enums\StatusState;
use App\Mail\DamagePenaltyIssuedMail;
use App\Models\Admin;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\SellerDamageStrike;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;
use App\Notifications\AdminDamagePenaltyPaidNotification;
use App\Notifications\AdminLegalActionRequiredNotification;
use App\Notifications\SellerDamagePenaltyIssuedNotification;
use App\Notifications\SellerPermanentlyBannedNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The physical-inspection verdict, the seller-pays-admin penalty that
 * follows a confirmed-damaged verdict, the three-strikes ban, and the
 * unpaid-penalty deadline. Seller access is snapshotted before it is
 * revoked so a later restore puts back exactly what the seller had, not a
 * blanket grant of both auction and direct-sell access.
 */
class DamagePenaltyService
{
    public const STRIKE_LIMIT = 3;

    public static function penaltyFor(Order $order): float
    {
        return round((float) $order->total_amount * (float) config('services.esewa.damage_penalty_percentage', 30) / 100, 2);
    }

    /**
     * Confirmed-damaged verdict: revoke the seller's access immediately and
     * issue the penalty. The buyer refund is handled by the caller
     * (OrderCancellationService) since it reuses the existing refund logic
     * unchanged.
     */
    public static function issue(Order $order): DamagePenalty
    {
        return DB::transaction(function () use ($order): DamagePenalty {
            $seller = User::whereKey($order->seller_id)->lockForUpdate()->first();

            // If the seller already has another unresolved penalty, access
            // was already revoked by it - reading the seller's *current*
            // flags here would snapshot the already-revoked false/false as
            // this penalty's "prior" state. Reuse that penalty's snapshot
            // (the true original state) instead, so restoring access later
            // via this penalty doesn't re-revoke access already restored.
            $existingUnresolved = DamagePenalty::where('seller_id', $seller->id)
                ->where('status', DamagePenaltyStatus::PENDING)
                ->latest()
                ->first();

            $penalty = DamagePenalty::create([
                'order_id' => $order->id,
                'seller_id' => $seller->id,
                'amount' => self::penaltyFor($order),
                'status' => DamagePenaltyStatus::PENDING,
                'verdict_recorded_at' => now(),
                'due_at' => now()->addDays((int) config('services.esewa.damage_penalty_days', 7)),
                'prior_is_auction_allowed' => $existingUnresolved?->prior_is_auction_allowed ?? $seller->is_auction_allowed,
                'prior_is_seller' => $existingUnresolved?->prior_is_seller ?? $seller->is_seller,
            ]);

            // Access is being actively revoked as a penalty, not left "under
            // review" - a stale pending flag would otherwise show the seller
            // a "Request Pending" state in the navbar instead of reflecting
            // what actually happened.
            $seller->update(['is_auction_allowed' => false, 'is_seller' => false, 'seller_application_pending' => false]);

            return $penalty;
        });
    }

    public static function notifySeller(DamagePenalty $penalty): void
    {
        self::notifySafely($penalty->seller, new SellerDamagePenaltyIssuedNotification($penalty));

        try {
            Mail::to($penalty->seller->email)->send(new DamagePenaltyIssuedMail($penalty));
        } catch (\Throwable $exception) {
            Log::warning('Damage-penalty email could not be sent.', [
                'seller_id' => $penalty->seller_id,
                'damage_penalty_id' => $penalty->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Seller's eSewa payment cleared: log it, count the strike, ban on the
     * third one. Restoring access (short of a permanent ban) is a separate,
     * manual admin action - see restoreAccess().
     */
    public static function recordPayment(DamagePenalty $penalty): void
    {
        $resolved = false;

        $strikeCount = DB::transaction(function () use ($penalty, &$resolved): ?int {
            // Re-checked under a row lock so a duplicate/replayed eSewa
            // callback (refresh, back-forward, two tabs) can't both pass the
            // controller's guard and double-record the same payment.
            $current = DamagePenalty::whereKey($penalty->id)->lockForUpdate()->first();

            if (! $current || $current->status !== DamagePenaltyStatus::PENDING) {
                return null;
            }

            $resolved = true;

            $current->update(['status' => DamagePenaltyStatus::PAID, 'paid_at' => now()]);

            PaymentTransaction::create([
                'order_id' => $current->order_id,
                'type' => PaymentTransactionType::DAMAGE_PENALTY_PAID,
                'amount' => $current->amount,
                'payment_method' => PaymentTransactionMethod::ESEWA,
                'status' => PaymentTransactionStatus::COMPLETED,
                'party' => PaymentTransactionParty::ADMIN,
                'notes' => 'Damage penalty paid by seller '.$current->seller->name.'.',
            ]);

            SellerDamageStrike::create([
                'seller_id' => $current->seller_id,
                'order_id' => $current->order_id,
                'damage_penalty_id' => $current->id,
                'amount' => $current->amount,
            ]);

            $count = SellerDamageStrike::where('seller_id', $current->seller_id)->count();

            if ($count >= self::STRIKE_LIMIT) {
                $current->seller->update([
                    'is_permanently_banned' => true,
                    'permanent_ban_reason' => 'three_strikes',
                    'permanently_banned_at' => now(),
                ]);
            }

            return $count;
        });

        if (! $resolved) {
            return;
        }

        Admin::all()->each(function (Admin $admin) use ($penalty, $strikeCount): void {
            self::notifySafely($admin, new AdminDamagePenaltyPaidNotification($penalty, $strikeCount));

            if ($strikeCount >= self::STRIKE_LIMIT) {
                self::notifySafely($admin, new SellerPermanentlyBannedNotification($penalty->seller));
            }
        });
    }

    /**
     * Manual admin action after a paid penalty: restores exactly the access
     * the seller had before revocation. Refuses if the seller was
     * permanently banned in the meantime (e.g. this was their third strike).
     */
    public static function restoreAccess(DamagePenalty $penalty, Admin $admin): bool
    {
        if (! $penalty->canBeRestored() || $penalty->seller->is_permanently_banned) {
            return false;
        }

        // Access must stay revoked while any other penalty from this seller
        // is still unresolved - otherwise restoring via this one paid
        // penalty would grant access back while another unpaid one exists.
        $hasOtherUnresolvedPenalty = DamagePenalty::where('seller_id', $penalty->seller_id)
            ->whereKeyNot($penalty->id)
            ->where('status', DamagePenaltyStatus::PENDING)
            ->exists();

        if ($hasOtherUnresolvedPenalty) {
            return false;
        }

        DB::transaction(function () use ($penalty, $admin): void {
            $penalty->seller->update([
                'is_auction_allowed' => $penalty->prior_is_auction_allowed,
                'is_seller' => $penalty->prior_is_seller,
            ]);

            $penalty->update(['restored_at' => now(), 'restored_by_admin_id' => $admin->id]);
        });

        return true;
    }

    /**
     * Daily check: a pending penalty past its due date deactivates the whole
     * account and flags the order for legal action.
     */
    public static function expireOverdue(): void
    {
        $admins = Admin::all();

        DamagePenalty::where('status', DamagePenaltyStatus::PENDING)
            ->where('due_at', '<', now())
            ->with(['seller', 'order'])
            ->each(function (DamagePenalty $penalty) use ($admins): void {
                $resolved = false;

                DB::transaction(function () use ($penalty, &$resolved): void {
                    // Re-checked under a row lock so a payment that clears in
                    // the window between this batch's read and its update
                    // can't be overwritten back to expired.
                    $current = DamagePenalty::whereKey($penalty->id)->lockForUpdate()->first();

                    if (! $current || $current->status !== DamagePenaltyStatus::PENDING) {
                        return;
                    }

                    $resolved = true;
                    $current->update(['status' => DamagePenaltyStatus::EXPIRED, 'expired_at' => now(), 'legal_action_flagged' => true]);
                    $current->seller->update(['status' => StatusState::INACTIVE->value]);
                });

                if (! $resolved) {
                    return;
                }

                self::notifySafely($penalty->seller, new AccountStatusChangedNotification(StatusState::INACTIVE->value));

                $admins->each(function (Admin $admin) use ($penalty): void {
                    self::notifySafely($admin, new AdminLegalActionRequiredNotification($penalty));
                });
            });
    }

    /**
     * These notifications broadcast in-process (ShouldBroadcastNow). A
     * transient Reverb hiccup must not turn an already-committed DB change
     * (a payment recorded, an account deactivated) into a fatal error for
     * whoever's request triggered it - the callback controller and the
     * scheduled command both need to finish regardless.
     */
    private static function notifySafely(object $notifiable, object $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (BroadcastException $exception) {
            Log::warning('Damage-penalty notification broadcast failed.', [
                'notifiable' => $notifiable::class,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
