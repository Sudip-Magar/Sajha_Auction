# eSewa Payment Gateway Integration — What Changed & How to Set It Up

**Date:** 2026-09-08 (eSewa integration), updated 2026-09-08 (cancellation reasons)
**Branch:** `sudeep/second-device`
**Scope:** Sandbox/test-mode eSewa integration for a single purpose — a small deposit
that secures an **auction win**, so the winning bidder can't just disappear before the
in-person meetup. This is **not** a general payment gateway for the regular buy/sell
flow — that stays cash-on-meetup/cash-on-delivery by design, since buyers are expected
to inspect the item in person before paying the seller.

This document lists **every file this feature touched**, including the one change made
outside the project folder, and gives step-by-step instructions to reproduce the setup
on a different machine, or to add the same kind of integration to a brand-new project.

Section 7 covers a follow-up feature: requiring a **reason when cancelling an order**,
so a paid auction deposit is refunded or forfeited depending on why the deal fell
through, instead of just silently disappearing either way.

---

## 1. Why this exists

- The project is a final-year student project, not going to production, so there was no
  need for a real merchant account or real money.
- eSewa (and Khalti) both provide free **sandbox/test environments** — fake credentials,
  fake money, but the exact same API flow you'd use in production. That's enough to
  demo a fully working integration without any business registration or fees.
- Only eSewa was implemented (Khalti was intentionally skipped — "one gateway is enough
  for now").

---

## 2. Files changed inside the project

### New files

| File | Purpose |
|---|---|
| `database/migrations/2026_09_08_163309_add_deposit_fields_to_orders_table.php` | Adds `auction_id`, `deposit_amount`, `deposit_status`, `deposit_transaction_uuid` columns to the `orders` table. |
| `app/Services/EsewaPaymentService.php` | All eSewa-specific logic: builds the signed payment form, verifies the signed callback, and double-checks payment status via eSewa's status API. |
| `app/Http/Controllers/EsewaPaymentController.php` | Three routes' worth of logic: `initiate` (start a payment), `success` (eSewa redirected back happy), `failure` (eSewa redirected back sad/cancelled). |
| `app/Notifications/DepositPaidNotification.php` | Notifies the seller when a deposit is paid (same pattern as the existing `AuctionWonNotification`/`OutbidNotification`). |
| `resources/views/payment/esewa-redirect.blade.php` | A tiny auto-submitting HTML form that POSTs the signed payment fields to eSewa's payment page (eSewa requires a real form POST, not a simple link). |
| `tests/Feature/EsewaPaymentTest.php` | 6 tests: signature generation is correct, signature verification rejects tampering, buyer-only authorization, and that a deposit is only marked "paid" when eSewa's status API independently agrees. |

### Modified files

| File | What changed |
|---|---|
| `app/Models/Order.php` | Added the 4 new deposit columns to `$fillable`/`$casts`, added an `auction()` relationship, added a `needsDeposit()` helper method. |
| `app/Services/AuctionEngineService.php` | When an auction is won and its `Order` is auto-created, it now also sets `auction_id`, `deposit_amount` (10% of the winning price by default), and `deposit_status = 'pending'`. |
| `config/services.php` | Added an `esewa` config block reading `ESEWA_*` env vars, defaulting to eSewa's public sandbox test values. |
| `.env.example` | Added the same `ESEWA_*` variables with sandbox defaults, so anyone cloning the repo gets a working setup with zero configuration. |
| `.env` *(not committed to git, see below)* | Same `ESEWA_*` variables added to your actual local environment, so it works right now on this machine. |
| `routes/web.php` | Added 3 routes: `payment.esewa.initiate` (buyer-only, requires login), `payment.esewa.success`, `payment.esewa.failure` (public — eSewa redirects the browser here, so these can't require an active session). |
| `app/Livewire/User/OrderDetail.php` | `mount()` now checks for a flash message from the payment controller and shows it as a toast. |
| `app/Livewire/User/Orders.php` | Same flash-message toast handling, added a `mount()` method (there wasn't one before). |
| `app/Livewire/User/Notifications.php` | Added a case so clicking a "deposit paid" notification takes the seller to the right order page. |
| `resources/views/livewire/user/order-detail.blade.php` | Added the "Auction Win Deposit" card — shows the deposit amount/status, and a "Pay Deposit via eSewa" button for the buyer when it's still pending. |

### Database

Running `php artisan migrate` applied the new deposit-fields migration above, **and** also
finally ran a migration left pending from the previous session
(`2026_09_07_175028_drop_bookmarks_table` — an unrelated cleanup of a duplicate
wishlist/bookmarks feature). Both are now applied to your real MySQL database.

---

## 3. The one change made *outside* the project folder

**File:** `C:\php\php.ini` (your system-wide PHP configuration — not part of this git
repo, not something `git status` will ever show)

**What changed:** two lines were uncommented:

```ini
extension=pdo_sqlite
extension=sqlite3
```

**Why:** this project's automated test suite (`php artisan test`) is configured to run
against an in-memory SQLite database (see `phpunit.xml`). Those two PHP extensions were
disabled on this machine, so **the entire test suite** — not just my new tests — was
failing before I even started, with `could not find driver`. I asked for your
permission before touching a file outside the project, you approved it, and I enabled
them so I could actually verify the new eSewa tests (and confirm I hadn't broken any
existing ones) instead of just claiming they'd work.

**Is this required for the app to run?** No. The live app uses MySQL
(`DB_CONNECTION=mysql` in your `.env`), so this change only matters for running
`php artisan test` locally. If you move to a new machine and don't plan to run the test
suite there, you can skip this step — the eSewa feature itself doesn't depend on it.

---

## 4. Setting this up on a different machine

If you're cloning this repo fresh onto another laptop/computer:

1. **Clone the repo and install dependencies** as usual:
   ```
   composer install
   npm install
   ```
2. **Copy `.env.example` to `.env`** (if you don't already have one) — the eSewa sandbox
   variables are already in there with working defaults, so no extra setup is needed:
   ```
   ESEWA_PRODUCT_CODE=EPAYTEST
   ESEWA_SECRET_KEY="8gBm/:&EnhH.1/q"
   ESEWA_FORM_URL=https://rc-epay.esewa.com.np/api/epay/main/v2/form
   ESEWA_STATUS_URL=https://rc.esewa.com.np/api/epay/transaction/status/
   ESEWA_DEPOSIT_PERCENTAGE=10
   ```
   These are eSewa's own publicly-documented sandbox test credentials — safe to commit,
   not a secret.
3. **Run migrations** (this creates the new deposit columns, among everything else):
   ```
   php artisan migrate
   ```
4. **(Optional) Run the test suite.** If you get `could not find driver`, open your
   PHP's `php.ini` (find its path with `php --ini`) and uncomment:
   ```
   extension=pdo_sqlite
   extension=sqlite3
   ```
   Then re-run `php artisan test`.
5. **That's it.** No eSewa account, business registration, or API keys needed — the
   sandbox credentials work immediately.

### Trying it out end-to-end

1. Win an auction as a test buyer (or use an existing won auction in your seeded data).
2. Go to **My Orders → [that order]**. You'll see an "Auction Win Deposit" card with a
   "Pay Deposit via eSewa" button.
3. Click it — you'll be redirected to eSewa's sandbox payment page.
4. Log in with eSewa's public test account:
   - **eSewa ID:** `9711111111` (or `...2`/`...3`)
   - **Password:** `Test@123`
   - **MPIN (if asked, app only):** `1122`
5. Complete the fake payment. You'll be redirected back to the order page, which now
   shows the deposit as **PAID**, and the seller gets notified.

No real money moves at any point — this is entirely eSewa's sandbox environment.

---

## 5. Reusing this pattern in a new project

If you want to add eSewa (ePay v2) payments to a different Laravel project later, the
shape of the work is the same regardless of the project:

1. **Config:** add an `esewa` block to `config/services.php` reading `product_code`,
   `secret_key`, `form_url`, `status_url` from env vars (sandbox defaults as above).
2. **Signing service:** a small class that:
   - builds the payment form fields (`amount`, `tax_amount`, `product_service_charge`,
     `product_delivery_charge`, `total_amount`, `transaction_uuid`, `product_code`,
     `success_url`, `failure_url`, `signed_field_names`), and signs
     `total_amount,transaction_uuid,product_code` (in that exact order) with
     HMAC-SHA256, base64-encoded, using the secret key.
   - decodes eSewa's base64 `?data=` callback and **re-verifies its signature** the
     same way before trusting it.
   - calls eSewa's status-check API as a second, independent confirmation — never trust
     the browser redirect alone, since a user's browser is not a secure channel.
3. **Auto-submit form view:** eSewa requires an actual HTML form POST to its payment
   page — there's no simple "redirect to URL" option — so you need a tiny blade page
   that renders hidden inputs and submits itself via JS on page load.
4. **Three routes:** one to start the payment (behind auth, scoped to "does this user
   own this thing they're paying for"), and two public ones for eSewa's success/failure
   redirects (public because the redirect is just a browser navigation — you can't
   guarantee session cookies survive the round trip in every environment, so match by
   the transaction ID you generated, not by "who's logged in").
5. **Whatever model represents the payable thing** (an order, a booking, etc.) needs a
   status field to track pending/paid/failed, and a unique transaction UUID column so
   the callback can be matched back to the right record.

Docs reference: https://developer.esewa.com.np/pages/Epay

---

## 6. Going to production (not needed right now, but for reference)

This project intentionally stays in sandbox mode. If a future project needs to go live:

1. Register as an eSewa merchant (business documents required) to get a real
   `product_code` and `secret_key`.
2. Swap `ESEWA_FORM_URL`/`ESEWA_STATUS_URL` to the production hosts
   (`epay.esewa.com.np`/`esewa.com.np` instead of the `rc-` sandbox subdomains).
3. Everything else — the code, the signing logic, the routes — stays identical. That's
   the entire point of building against the sandbox first.

---

## 7. Cancellation reasons & deposit refund/forfeit logic

**Problem this solves:** a buyer wins an auction, pays the eSewa deposit to secure it,
then meets the seller and finds the item is defective or doesn't match the listing.
Should they lose their deposit for backing out? Before this change, cancelling an order
never touched `deposit_status` at all — the deposit just sat there marked "paid"
forever, with no way to tell "buyer had a legitimate reason" from "buyer just flaked."

**The rule implemented:**

- **Seller cancels** → deposit is always marked `refund_owed` (the buyer did nothing
  wrong).
- **Buyer cancels because the item had a defect or didn't match the listing** →
  `refund_owed`.
- **Buyer cancels because they simply changed their mind (or gave no real reason)** →
  `forfeited`.

`refund_owed` doesn't auto-refund anything — eSewa refunds need a separate API call and
this is a student project, so it's intentionally a manual step (the seller/admin sees
the "REFUND OWED TO BUYER" badge on the order page and handles it outside the app).

**Also fixed in the same change:** `AuctionEngineService::determineWinner()` marks the
product `status = 'sold'` the instant an auction ends — before any deposit is paid or
meetup happens. Previously, if that order was later cancelled, the product stayed
`'sold'` forever with no way for the seller to list it again. `OrderCancellationService`
now relists it (`status` back to `'active'`) whenever an auction-origin order
(`order.auction_id` is set) is cancelled and its product is still marked `'sold'`. The
concluded `Auction` record itself is left untouched — it stays `'completed'` with its
winner/winning price as accurate history; only the product becomes available again for
a fresh listing.

### New/changed files

| File | What it does |
|---|---|
| `database/migrations/2026_09_08_170309_add_cancellation_reason_to_orders_table.php` | Adds `cancellation_reason_category` and `cancellation_note` columns to `orders`. |
| `app/Services/OrderCancellationService.php` | **New.** The single place that decides refund vs. forfeit, applies it, and relists the product if the order came from an auction win — `OrderDetail` and `Orders` both call into this so the logic can't drift between the two pages. |
| `app/Models/Order.php` | Added the two new columns to `$fillable`. |
| `app/Livewire/User/OrderDetail.php` | Cancelling is no longer a single click — `toggleCancelForm()` reveals a small form, `confirmCancel()` collects the reason and calls `OrderCancellationService::cancel()`. The old generic `updateOrderStatus()` no longer accepts `'cancelled'` at all (it's rejected as an invalid status) so cancellation can't accidentally skip the reason step. |
| `app/Livewire/User/Orders.php` (the orders **list** page) | Same lockout — `updateOrderStatus()` now only accepts `confirmed`/`completed`. This page's "Cancel Order" button no longer cancels inline; a list of many orders isn't a good place for a reason-selection form, so it now just links to that order's detail page instead. |
| `resources/views/livewire/user/order-detail.blade.php` | New "Why are you cancelling?" form (radio buttons for the buyer, free-text note for both), and the deposit status card now shows 4 states instead of 2: `PENDING` / `PAID` / `REFUND OWED TO BUYER` / `FORFEITED`. Also shows the recorded reason once an order is cancelled. |
| `resources/views/livewire/user/orders.blade.php` | "Cancel Order" is now a link to the detail page, not a button. |
| `tests/Feature/OrderStatusAuthorizationTest.php` | Updated the existing cancel test for the new API; added tests for the refund path, the forfeit path, seller-initiated cancellation always being refund-owed, the product relisting on an auction-win cancellation, and confirming a regular direct-sell cancellation leaves the product untouched. |

### Setup impact

No new environment variables. Just run `php artisan migrate` to pick up the new
columns — same as any other change in this repo.

### Reusing this pattern elsewhere

The general shape — "cancelling something with money on the line should ask *why*, and
that reason should drive an automatic refund/forfeit decision instead of a human having
to remember to check" — applies to any deposit/booking-fee system, not just auctions.
The key design choices worth keeping:
1. Centralize the decision in one service class both UI entry points call, rather than
   duplicating the if/else in every place a cancel button exists.
2. Make the *old* unrestricted status-update path explicitly reject the state that now
   requires extra input, so there's no accidental bypass of the reason form.
3. Let the *other* party's cancellation (here: the seller) skip the reason picker
   entirely — they're not the one whose intent is in question.

---

## 8. Admin order visibility + small cleanups (2026-09-08)

Three follow-up items decided after reviewing what was still open:

1. **Admin can now see every order.** New page at `/admin/orders`
   (`app/Livewire/Admin/Orders.php` + `resources/views/livewire/admin/orders.blade.php`,
   route `admin.orders`, linked in the sidebar). Lists every order — second-hand and
   auction — with its product, buyer/seller, amount, deposit status (including
   REFUND OWED / FORFEITED), and order status (pending/confirmed/meetup
   scheduled/completed/cancelled), plus search and filter by type/status. This was a
   deliberate addition, not automatic — the previous state (admin has zero order
   visibility) was flagged as possibly intentional for a pure peer-to-peer platform, and
   the user decided admin oversight was wanted after all. Test:
   `tests/Feature/AdminOrdersTest.php`.
2. **`Checkout.php:150`** now uses `Carbon::parse(...)` instead of `now()->parse(...)` —
   purely cosmetic, same behavior.
3. **The orders list page** (buyer/seller-facing, not admin) now shows a small deposit
   status badge (Deposit Pending/Paid/Refund Owed/Forfeited) inline on each order card,
   instead of only being visible after opening that order's detail page.

No new environment variables or setup steps for any of these — just
`php artisan migrate` isn't even needed (no schema changes), the admin page uses columns
that already existed from sections 3 and 7 above.
