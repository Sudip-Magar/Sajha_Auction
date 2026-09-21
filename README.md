# Sajha Auction

Sajha Auction is a Nepal-focused marketplace for **second-hand products**, sold either directly at a listed price or through **live auctions**. Buyers and sellers meet in person, the buyer inspects the item, and payment is made in cash at the handover (like OLX or Facebook Marketplace, with auctions added). It has separate experiences for users and for admins, real-time bidding, in-app chat, and an AI assistant that explains how the site works.

It is built as a final-year project.

## Table of contents

- [Features](#features)
- [How it works](#how-it-works)
- [Tech stack](#tech-stack)
- [Installation](#installation)
- [Running the project](#running-the-project)
- [Configuration reference](#configuration-reference)
- [Project structure](#project-structure)
- [Testing and code style](#testing-and-code-style)
- [More documentation](#more-documentation)
- [Known limitations](#known-limitations)

## Features

**For buyers**
- Browse, search and filter second-hand products and live auctions (no account needed to browse).
- Register with an email OTP or sign in with Google; complete a profile (age 16+).
- Wishlist, cart, and checkout for second-hand items (in-person meetup, cash on handover).
- Live bidding with manual and proxy (automatic) bids, outbid alerts, and anti-sniping auto-extend.
- In-app chat with sellers, order tracking, and order cancellation with a reason.
- Notifications in real time (Laravel Reverb + Echo).
- AI assistant (Google Gemini) that explains how auctions and ordering work, available as a floating icon and drawer on every page.

**For sellers**
- Apply for seller access; list second-hand products or auctions with photos, condition, price and meetup location.
- Auction listings have a schedule (Nepali B.S. date pickers), starting bid, optional reserve price and a minimum bid step, with suggested values.
- Get notified when someone orders, confirm the order, and mark it completed after the handover.

**For admins** (separate login and guard)
- Dashboard, seller request approval, auction-access application review, and product/auction approval.
- User management (activate/deactivate, seller and auction access), category and sub-category setup, FAQs.
- Monitor all orders, and manage site settings.

## How it works

### Roles

| Role | How it is represented |
|---|---|
| Guest | Not logged in; can browse |
| User (buyer) | `users` row, logged in with the `web` guard |
| Seller | `users.is_seller = true`, approved by an admin |
| Auction bidder or host | `users.is_auction_allowed = true`, approved by an admin after a document application |
| Admin | Separate `admins` table with its own `admin` guard |

### Second-hand purchase flow

1. A seller lists a product; an admin approves it.
2. A buyer adds it to the cart or uses **Buy now** and goes to checkout.
3. Checkout has one handover method (in-person meetup) and one payment option (cash on meetup). The buyer enters a phone number, a meetup location, and a required meetup date (Nepali B.S. date picker) and time.
4. One order is created per seller and the seller gets a real-time notification.
5. The seller confirms the order, they meet, the buyer inspects the item and pays in cash, and the seller marks the order completed. Either side can cancel with a reason.

### Auction flow

1. A seller approved for auctions lists an item with a start and end time, starting bid, optional reserve price and minimum bid increment. An admin approves it and it goes live at the start time.
2. Bidders place manual bids or set a secret maximum (proxy bidding). The engine (`app/Services/AuctionEngineService.php`) runs inside a database transaction with a row lock, so simultaneous bids are handled safely. It provides:
   - a dynamic minimum step that grows with the price (the seller's increment can only raise it),
   - proxy bidding, where the leader pays one step above the runner-up's maximum, never more than their own,
   - anti-sniping: a bid near the deadline extends the end time,
   - live updates through the `auctions.{id}` broadcast channel.
3. When the auction ends (every minute via the scheduler, or when someone opens or bids on the auction), the highest bid at or above the reserve wins; ties go to the earliest bid. If nothing reaches the reserve, the auction ends unsold.
4. An order is created automatically for the winner. The winner pays a deposit (default 10%, `ESEWA_DEPOSIT_PERCENTAGE`) through eSewa and the balance in cash at the meetup. If the order is cancelled after the deposit was paid, it is marked as refund owed or forfeited depending on who cancelled and why.

### AI assistant

A separate, informational-only assistant (`app/Livewire/User/AiAssistant.php`). It has no access to user, order or bid data. Asking a question stores it and shows the typing dots at once; the browser then immediately requests the answer in a second request (`app/Jobs/GenerateAiAssistantReply.php` holds the logic), so no queue worker is needed and a first answer takes about 2 to 3 seconds. Answers to a repeated first question (such as the suggestion chips) are cached for 12 hours and appear instantly. A polling safety net answers a message that was left unanswered. If Gemini is overloaded, the next model in `AI_FALLBACK_MODEL` is tried. One conversation is kept per logged-in user, or per browser session for guests.

## Tech stack

- PHP 8.3, Laravel 13
- Livewire 4, Alpine.js 3, Tailwind CSS 4, daisyUI, Mary UI
- Laravel Reverb (WebSockets) and Laravel Echo
- Laravel Socialite (Google sign-in)
- MySQL (SQLite is used for the automated tests)
- Vite, Pest 4
- Google Gemini API (AI assistant), eSewa ePay v2 (auction deposit, sandbox mode)

## Installation

### Requirements

- PHP 8.3 or newer with the `mbstring`, `openssl`, `pdo_mysql`, `fileinfo` and `curl` extensions (enable `pdo_sqlite` too if you want to run the tests)
- Composer 2
- Node.js 20.19 or newer (or 22.12+) and npm
- MySQL 8 (or MariaDB)

### Steps

1. **Get the code and install dependencies**

   ```bash
   git clone <your-repository-url> Sajha_Auction
   cd Sajha_Auction
   composer install
   npm install
   ```

2. **Create the environment file**

   ```bash
   cp .env.example .env        # on Windows PowerShell: copy .env.example .env
   php artisan key:generate
   ```

3. **Create an empty MySQL database** (for example `sajha_auction`) and edit `.env`:

   ```env
   APP_NAME="Sajha Auction"
   APP_URL=http://127.0.0.1:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sajha_auction
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password
   ```

4. **Configure real-time (Reverb).** Set `BROADCAST_CONNECTION=reverb` and add these lines (any random strings work for the id, key and secret in local development):

   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=sajha-local
   REVERB_APP_KEY=local-key
   REVERB_APP_SECRET=local-secret
   REVERB_HOST=127.0.0.1
   REVERB_PORT=8080
   REVERB_SCHEME=http

   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

   Without Reverb, live bids, chat and notification pop-ups only appear after a page refresh.

5. **Configure email (used for OTP codes).** The default `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`, so you can copy the 6-digit OTP from the log while developing. To send real email, use SMTP:

   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your_email
   MAIL_PASSWORD=your_app_password
   MAIL_FROM_ADDRESS=your_email
   MAIL_FROM_NAME="${APP_NAME}"
   ```

   OTP emails are sent immediately (they are not queued), so no queue worker is needed for them.

6. **Optional integrations**

   ```env
   # Google sign-in (redirect URI must be registered in Google Cloud Console)
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   GOOGLE_REDIRECT_URL=http://127.0.0.1:8000/auth/google/callback

   # AI assistant (Google Gemini)
   AI_PROVIDER=gemini
   AI_API_KEY=your_gemini_api_key
   AI_MODEL=gemini-3.5-flash-lite
   AI_FALLBACK_MODEL=gemini-3.8-flash,gemini-flash-lite-latest
   AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
   ```

   Without a Gemini key the assistant still opens but replies with a friendly "could not reach the assistant" message. eSewa uses eSewa's public sandbox credentials by default (see `config/services.php`), so it needs no setup for development.

7. **Create the tables, seed the admin, and link storage**

   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan storage:link
   ```

   `db:seed` creates the default admin account (see below). The `storage:link` step is required for product and profile images to show.

8. **Build the frontend** (needed once, or use the dev server in the next section)

   ```bash
   npm run build
   ```

### Default admin account (local only)

- Login page: `/admin/login`
- Email: `admin@sajhaauction.com`
- Password: `password`

Change these for any non-local environment.

There are no seeded buyer accounts. Register through `/register`, then use the OTP from your inbox or from `storage/logs/laravel.log`.

## Running the project

### Recommended

```bash
composer run dev
```

This starts the web server, the queue worker, the Reverb WebSocket server, the log viewer (Pail) and the Vite dev server together. Then open http://127.0.0.1:8000.

### Also run the scheduler

Auctions are settled once a minute by the scheduler, which `composer run dev` does not start. In another terminal run:

```bash
php artisan schedule:work
```

Without it, an ended auction is still settled when someone opens its page or places a bid, but not automatically.

### Manual alternative

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan reverb:start
php artisan schedule:work
npm run dev
```

### Trying the whole flow

1. Log in to `/admin/login` as the seeded admin.
2. Register a normal user, open **Join Auction** or request seller access, then approve the request from the admin panel (`/admin/seller-requests`, `/admin/auction-application`).
3. As the approved seller, create a listing at `/my-products`; as admin, approve it under `/admin/products`.
4. As a second user, buy the item or bid on the auction.

## Configuration reference

| Area | Variables | Notes |
|---|---|---|
| App | `APP_URL`, `APP_DEBUG` | Set `APP_DEBUG=false` in production |
| Database | `DB_*` | MySQL by default |
| Queue, session, cache | `QUEUE_CONNECTION`, `SESSION_DRIVER`, `CACHE_STORE` | All `database` by default, so run `php artisan migrate` first. No feature needs a queue worker by default |
| Real-time | `BROADCAST_CONNECTION`, `REVERB_*`, `VITE_REVERB_*` | `log` disables live updates |
| Mail | `MAIL_*` | `log` writes emails to the log file |
| Google login | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URL` | Optional |
| eSewa deposit | `ESEWA_PRODUCT_CODE`, `ESEWA_SECRET_KEY`, `ESEWA_FORM_URL`, `ESEWA_STATUS_URL`, `ESEWA_DEPOSIT_PERCENTAGE` | Defaults to the public sandbox; deposit defaults to 10% |
| AI assistant | `AI_PROVIDER`, `AI_API_KEY`, `AI_MODEL`, `AI_FALLBACK_MODEL`, `AI_BASE_URL` | Optional; only the Gemini provider is implemented. `AI_FALLBACK_MODEL` takes a comma-separated list tried in order when the main model is overloaded or retired |

After changing `.env` values while a server is running, restart it (and run `php artisan config:clear` if you have cached config).

## Project structure

```text
app/
  Enums/            Product, order, payment and status enums (each with a label())
  Events/           AuctionBidPlaced, ChatMessageSent (broadcast)
  Http/Controllers/ GoogleAuthController, EsewaPaymentController
  Http/Middleware/  UserMiddleware, AdminMiddleware, EnsureGoogleSession
  Jobs/             GenerateAiAssistantReply
  Livewire/         Admin/, Auth/, User/, Components/ (navbar, footer, topbar ...)
  Models/           User, Admin, Product, Auction, Bid, Order, Conversation ...
  Notifications/    Database + broadcast notifications for users and admins
  Services/         AuctionEngineService, AuctionValuationService,
                    OrderCancellationService, EsewaPaymentService,
                    AiClientService, AiAssistantPromptService
database/           Migrations, seeders (AdminSeeder), factories
resources/views/    Blade views (livewire/, layouts/, components/)
routes/             web.php, channels.php (broadcast auth), console.php (scheduler)
tests/Feature/      Pest and PHPUnit feature tests
docs/               ESEWA_PAYMENT_INTEGRATION.md
public/assets/      diagrams/ and refactor-diagrams/ (PlantUML files for the report)
```

Layouts: `layouts.app` for the user site and `layouts.admin` for the admin panel.

## Testing and code style

```bash
php artisan test --compact          # run the test suite
vendor/bin/pint --dirty --format agent   # format changed PHP files
```

The tests use SQLite in memory, so the `pdo_sqlite` PHP extension must be enabled.

## More documentation

- `docs/ESEWA_PAYMENT_INTEGRATION.md` explains the eSewa deposit integration and the cancellation and refund rules.
- `public/assets/diagrams/` and `public/assets/refactor-diagrams/` contain PlantUML diagrams (use case, class, sequence, activity, component, deployment). The `refactor-diagrams` versions are sized to fit an A4 page.

## Known limitations

- The eSewa integration runs against eSewa's **sandbox** and is used only for the auction-win deposit. It is not set up for real money.
- Second-hand orders are cash-on-meetup only. There is no online payment or delivery for them by design.
- Refunds of a paid deposit are recorded (`refund_owed`) but paid back manually outside the app.
- Gemini models are sometimes overloaded (HTTP 503) or retired for new users (HTTP 404). The assistant tries the models in `AI_MODEL` then `AI_FALLBACK_MODEL`; if it still shows an error, check `storage/logs/laravel.log` for the exact reason and pick models your key can use.
- Change the seeded admin password and set `APP_DEBUG=false` before any public deployment.
