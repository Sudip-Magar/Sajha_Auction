# Codex Project Context

This file is a working memory summary for future Codex sessions in this repository. It is intended to reduce repeated repo-wide analysis and provide a fast map of the current architecture.

## Project Identity

- Name: Sajha Auction
- Framework: Laravel 13
- PHP: 8.3
- Frontend: Livewire 4, Alpine.js 3, Tailwind CSS 4, Mary UI
- Realtime: Laravel Reverb + Laravel Echo + Pusher JS client
- Auth: session guards with separate `web` and `admin` guards
- Test stack: Pest

## What The App Currently Is

The current implementation is a moderated marketplace / auction-prep platform with:

- guest landing page
- user auth with OTP verification
- Google OAuth start/callback
- optional seller application during onboarding
- admin approval of sellers
- seller product upload
- admin product approval
- real-time notifications for both user and admin
- admin settings persistence

The codebase does not currently contain an actual bidding engine, payment flow, or public product catalog flow.

## Route Map

### Public and user-facing

- `/`
  - redirects to `dashboard` when user is logged in
  - redirects to `home` when user is a guest
- `/home`
  - guest landing page
- `/login`
  - user login
- `/register`
  - registration entry
- `/verify-otp`
  - OTP verification
- `/complete-profile`
  - post-OTP profile completion
- `/dashboard`
  - authenticated user dashboard
- `/my-products`
  - seller product management
- `/notifications`
  - full user notifications page
- `/settings`
  - user settings page

### Admin

- `/admin/login`
- `/admin/dashboard`
- `/admin/category-setup`
- `/admin/seller-requests`
- `/admin/products`
- `/admin/notifications`
- `/admin/settings`

## Auth and Guard Model

- `web` guard uses `App\Models\User`
- `admin` guard uses `App\Models\Admin`
- `UserMiddleware` protects user-only pages
- `AdminMiddleware` protects admin pages

Important auth files:

- `app/Livewire/Auth/User/Login.php`
- `app/Livewire/Auth/User/Register.php`
- `app/Livewire/Auth/User/VerifyOtp.php`
- `app/Livewire/Auth/User/CompleteProfile.php`
- `app/Livewire/Auth/Admin/Login.php`
- `app/Http/Controllers/GoogleAuthController.php`
- `config/auth.php`

## User Onboarding Flow

### Email/OTP path

1. User starts at `/register`
2. Email is stored in session under `google_user`
3. User verifies OTP at `/verify-otp`
4. If user already exists, login happens there
5. If user is new, they continue to `/complete-profile`

### Google path

1. User hits `auth.google.redirect`
2. Callback stores Google profile data in session
3. User completes OTP verification
4. Existing users are logged in
5. New users go to profile completion

### Profile completion path

Profile completion creates or updates a `User` and can mark:

- `is_seller = false`
- `seller_application_pending = true` when seller is requested

If seller is requested, all admins are notified via `SellerRegisteredNotification`.

## Core Domain Models

### User

Key fields:

- `name`
- `email`
- `username`
- `google_id`
- `avatar`
- `phone`
- `date_of_birth_en`
- `gender`
- `bio`
- `is_verified`
- `is_seller`
- `seller_application_pending`
- `status`

### Admin

Separate auth model with broadcast notification channel:

- `receivesBroadcastNotificationsOn()` returns `App.Models.Admin.{id}`

### Product

Key fields:

- `user_id`
- `category_id`
- `name`
- `slug`
- `description`
- `price`
- `starting_bid`
- `auction_end`
- `type`
- `image`
- `is_approved`
- `status`

Behavior:

- slug is generated on create
- belongs to `User`
- belongs to `Category`

### Category

- supports `parent_id`
- has `parent()` and `children()` relations

### Setting

Simple key/value store used by admin settings UI.

Helpers:

- `Setting::get()`
- `Setting::set()`

## Livewire Areas

### User pages

- `App\Livewire\Home`
- `App\Livewire\User\Dashboard`
- `App\Livewire\User\Products`
- `App\Livewire\User\Settings`
- `App\Livewire\User\Notifications`

### User shared components

- `App\Livewire\Components\User\Navbar`
- `App\Livewire\Components\User\Footer`

Navbar responsibilities:

- logout
- fetch latest notifications
- mark popup notifications as read
- mark all popup notifications as read
- redirect from notification click

### Admin pages

- `App\Livewire\Admin\Dashboard`
- `App\Livewire\Admin\CategorySetup`
- `App\Livewire\Admin\SellerRequests`
- `App\Livewire\Admin\Products`
- `App\Livewire\Admin\Notifications`
- `App\Livewire\Admin\Settings`

### Admin shared components

- `App\Livewire\Components\Admin\Sidebar`
- `App\Livewire\Components\Admin\Topbar`

Topbar responsibilities:

- fetch latest admin notifications
- mark popup notifications as read
- mark all popup notifications as read
- redirect from notification click

## Notifications

Implemented notification classes:

- `SellerRegisteredNotification`
  - sent to admins when a user requests seller access
  - redirects admins to `admin.seller-requests`
- `SellerApprovedNotification`
  - sent to users when seller request is approved
  - redirects users to `user.products`
- `NewProductUploadedNotification`
  - sent to admins when a seller uploads a product
  - redirects admins to `admin.products`
- `ProductApprovedNotification`
  - sent to users when product is approved
  - redirects users to `user.products`

Storage and transport:

- stored in Laravel `notifications` table
- broadcast in real time
- UI listeners refresh Livewire components via `echo-notification:*`

Relevant files:

- `app/Notifications/*`
- `routes/channels.php`
- `resources/js/echo.js`

## Realtime Setup

Frontend:

- `resources/js/app.js` imports `./echo`
- `resources/js/echo.js` configures Echo with Reverb broadcaster

Backend:

- `config/broadcasting.php`
- `config/reverb.php`

Required env families:

- `BROADCAST_CONNECTION=reverb`
- `REVERB_*`
- `VITE_REVERB_*`

## Layouts

- `resources/views/layouts/app.blade.php`
  - used by guest home and user pages
  - includes user navbar + footer
- `resources/views/layouts/admin.blade.php`
  - used by admin pages
- `resources/views/layouts/admin-auth.blade.php`
  - admin login layout

## UI Behavior Worth Remembering

- User navbar now distinguishes guest `Home` from logged-in `Dashboard`
- User menu includes explicit `Dashboard`
- User menu has active styling for:
  - `home`
  - `dashboard`
  - `user.products`
- Notification popups for both user and admin include:
  - `Marked as Read`
  - `Show More`
- Full notification pages now link each notification to its destination route

## Seller and Product Approval Logic

### Seller approvals

- Admin acts in `App\Livewire\Admin\SellerRequests`
- `approveSeller(User $user)` sets:
  - `is_seller = true`
  - `seller_application_pending = false`
- user receives `SellerApprovedNotification`

### Product approvals

- Seller uploads in `App\Livewire\User\Products`
- Product starts with `is_approved = false`, `status = pending`
- Admin approves in `App\Livewire\Admin\Products`
- approval sets:
  - `is_approved = true`
  - `status = active`
- user receives `ProductApprovedNotification`

## Settings

Admin settings UI stores values in the `settings` table.

Current groups in use:

- general
- social
- security

Current examples:

- site name
- site email
- contact phone
- address
- maintenance mode
- facebook / twitter / instagram URLs
- session timeout
- password minimum length
- strong password requirement
- two-factor required toggle

User footer reads social URLs from settings.

## Database Notes

Important tables from current migrations:

- `users`
- `admins`
- `categories`
- `products`
- `notifications`
- `settings`
- default Laravel support tables for cache/jobs/sessions

Notable schema notes:

- `products.type` supports `sell` and `auction`
- `products.status` defaults to `pending`
- `notifications.id` is UUID
- settings are unique by `key`

## Seeding

`DatabaseSeeder` currently calls only `AdminSeeder`.

Default seeded admin:

- email: `admin@sajhaauction.com`
- password: `password`

Treat this as local/dev only.

## Useful Commands

Install:

```bash
composer install
npm install
```

App bootstrap:

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Recommended local dev:

```bash
composer run dev
```

This runs:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan reverb:start`
- `php artisan pail --timeout=0`
- `npm run dev`

Verification:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Current Testing Reality

- test coverage is minimal
- there is only a small baseline Pest suite at the moment
- most important behavior is currently verified by manual flow testing

## Known Gaps / Future Work Areas

- actual bidding lifecycle
- bid placement rules and validations
- public browse/catalog pages
- auction timer and expiry handling
- winner selection / sale completion
- better test coverage across auth, approvals, and notifications
- stronger settings enforcement in runtime behavior

## Cautions For Future Edits

- This repo may already contain unrelated local changes; do not assume clean git state.
- Do not document or commit real secrets from `.env`.
- When updating user navigation, remember guest `Home` and authenticated `Dashboard` are intentionally separate routes now.
- If frontend changes do not appear, Vite or build output may need to be restarted.
