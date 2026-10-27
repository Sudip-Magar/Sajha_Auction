<?php

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only fix: some existing rows have `approval_status` stored with the
 * wrong casing (e.g. 'APPROVED' instead of 'approved'), which crashes the
 * enum cast on read. Normalizes any casing variant to the enum's current
 * canonical (lowercase) value.
 *
 * Done row-by-row in PHP rather than a single `WHERE ... != ?` query: most
 * database default collations (MySQL's included) compare strings
 * case-insensitively, so 'APPROVED' and 'approved' would look equal to a
 * plain `!=` guard and never get rewritten. A driver-specific case-sensitive
 * comparison (MySQL's BINARY, for example) isn't portable to SQLite, which
 * the test suite runs on - so this compares in PHP instead, which behaves
 * the same on every driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        $validValues = array_map(fn (ProductApprovalStatus $case): string => $case->value, ProductApprovalStatus::cases());

        DB::table('products')->select('id', 'approval_status')->get()->each(function (object $row) use ($validValues): void {
            $normalized = strtolower((string) $row->approval_status);

            if ($row->approval_status !== $normalized && in_array($normalized, $validValues, true)) {
                DB::table('products')->whereKey($row->id)->update(['approval_status' => $normalized]);
            }
        });
    }

    public function down(): void
    {
        // Data-only casing fix; the original (incorrect) casing is not
        // meaningfully recoverable, so there is nothing safe to reverse.
    }
};
