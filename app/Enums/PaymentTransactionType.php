<?php

namespace App\Enums;

enum PaymentTransactionType: string
{
    case DEPOSIT_PAID = 'deposit_paid';
    case BALANCE_PAID_CASH = 'balance_paid_cash';
    case REFUND_ISSUED = 'refund_issued';
    case DEPOSIT_FORFEITED = 'deposit_forfeited';
    case PAYOUT_SENT = 'payout_sent';
    case DEBT_RECORDED = 'debt_recorded';
    case DEBT_RECOVERED = 'debt_recovered';
    case DAMAGE_PENALTY_PAID = 'damage_penalty_paid';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT_PAID => 'Paid online via eSewa',
            self::BALANCE_PAID_CASH => 'Paid in cash at handover',
            self::REFUND_ISSUED => 'Refund to buyer',
            self::DEPOSIT_FORFEITED => 'Deposit forfeited',
            self::PAYOUT_SENT => 'Payout sent',
            self::DEBT_RECORDED => 'Seller debt recorded',
            self::DEBT_RECOVERED => 'Seller debt recovered',
            self::DAMAGE_PENALTY_PAID => 'Damage penalty paid by seller',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? str((string) $value)->replace('_', ' ')->title()->value();
    }
}
