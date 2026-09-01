# Sajha Auction

Sajha Auction is a Laravel 13 application for a marketplace-style auction/sales platform with separate user and admin experiences. The current codebase focuses on authentication, seller onboarding, product approval, admin management, and real-time notifications.

## Current Stack

- PHP 8.3
- Laravel 13
- Livewire 4
- Alpine.js 3
- Tailwind CSS 4
- Mary UI
- Laravel Socialite
- Laravel Reverb
- Vite
- Pest

## Current Product Scope

Implemented in the current codebase:

- Public guest home page at `/home`
- User login, registration, OTP verification, and profile completion
- Google OAuth entry point via Socialite
- Separate admin login with its own guard
- Seller application workflow
- Admin approval for seller requests
- User product upload flow for seller accounts
- Admin product approval flow
- User and admin notification popups
- Full notification pages for both guards
- Admin settings management stored in the `settings` table
- Real-time notification delivery through Reverb + Echo

Not present yet in the current codebase:

- Actual bidding engine
- Checkout or payment flow
- Public auction browsing/catalog implementation
- User bid history and auction history pages

## Main Flows

### User flow

1. Guest lands on `/`, which redirects to `/home` when not authenticated.
2. User can register with email OTP flow or start Google OAuth.
3. New users complete profile details before account creation.
4. User can optionally request seller status during profile completion.
5. Admin approves seller requests.
6. Approved sellers can upload products from `/my-products`.
7. Admin approves uploaded products.
8. Users receive database + broadcast notifications for seller approval and product approval.

### Admin flow

1. Admin logs in at `/admin/login`.
2. Admin uses `/admin/dashboard` to monitor platform activity.
3. Admin reviews seller requests at `/admin/seller-requests`.
4. Admin reviews products at `/admin/products`.
5. Admin receives real-time notifications when users request seller access or upload products.
6. Admin manages system/social/security settings at `/admin/settings`.

## Important Routes

### Public / user

- `/` redirects to `dashboard` for logged-in users and `home` for guests
- `/home` guest landing page
- `/login` user login
- `/register` user registration start
- `/verify-otp` OTP verification
- `/complete-profile` user profile completion
- `/dashboard` authenticated user dashboard
- `/my-products` seller product management
- `/notifications` authenticated user notifications
- `/settings` authenticated user settings

### Admin

- `/admin/login`
- `/admin/dashboard`
- `/admin/category-setup`
- `/admin/seller-requests`
- `/admin/products`
- `/admin/notifications`
- `/admin/settings`

## Key Models

- `User`
  - supports normal users and seller accounts
  - important flags: `is_verified`, `is_seller`, `seller_application_pending`
- `Admin`
  - separate auth model and `admin` guard
- `Product`
  - belongs to `User` and `Category`
  - supports `sell` and `auction` types
  - approval controlled by `is_approved`
- `Category`
  - supports parent/child hierarchy
- `Setting`
  - key/value storage for admin-configured settings

## Notifications

Current notification classes:

- `SellerRegisteredNotification`
- `SellerApprovedNotification`
- `NewProductUploadedNotification`
- `ProductApprovedNotification`

Notifications are stored in the `notifications` table and broadcast in real time. Frontend listeners exist in:

- `app/Livewire/Components/User/Navbar.php`
- `app/Livewire/Components/Admin/Topbar.php`

## Local Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create environment file

```bash
copy .env.example .env
```

Then update `.env` with your local values.

Minimum required areas:

- app URL
- database connection
- mail credentials for OTP
- Google OAuth credentials
- Reverb credentials

Example values you will likely need:

```env
APP_NAME="Sajha Auction"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sajha_auction
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email
MAIL_FROM_NAME="${APP_NAME}"

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URL=http://127.0.0.1:8000/auth/google/callback

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_reverb_app_id
REVERB_APP_KEY=your_reverb_app_key
REVERB_APP_SECRET=your_reverb_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3. Generate app key and migrate

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`db:seed` currently creates the default admin account from `Database\\Seeders\\AdminSeeder`.

## Running Locally

### Recommended

```bash
composer run dev
```

This starts:

- Laravel app server
- queue listener
- Reverb websocket server
- Pail log viewer
- Vite dev server

### Manual alternative

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan reverb:start
npm run dev
```

## Testing and Formatting

Run tests:

```bash
php artisan test --compact
```

Format PHP changes:

```bash
vendor/bin/pint --dirty --format agent
```

## Default Admin Seed

The seeded admin account is defined in `database/seeders/AdminSeeder.php`.

Current default values in code:

- email: `admin@sajhaauction.com`
- password: `password`

Change these for any non-local environment.

## Notes

- User-facing layout is `layouts.app`.
- Admin-facing layout is `layouts.admin`.
- Real-time notification auth channels are defined in `routes/channels.php`.
- The project contains only minimal automated tests right now.
- The app currently behaves more like a moderated marketplace with auction-ready structure than a full live bidding platform.
