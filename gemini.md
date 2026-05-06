# Sajha Auction - Project Overview

## 🏆 Project Description
Sajha Auction is a modern online auction platform built to provide a real-time bidding system with authentication, social login, and clean UI components.

## 🛠️ Technology Stack
- **Backend:** Laravel (v13.0)
- **Programming Language:** PHP (^8.3)
- **Frontend Framework:** Livewire (v4.3)
- **JavaScript:** Alpine.js (^3.15.12)
- **CSS Framework:** Tailwind CSS (v4.0.0)
- **Build Tool:** Vite (^8.0.0)
- **Database:** MySQL (Configured via `.env`)

## 📦 Key Dependencies
- `laravel/socialite` (^5.27) - For Google OAuth login.
- `illuminate/mail` - For the email system.
- `laravel/boost` - For agentic development workflow.
- `pestphp/pest` (^4.6) - For testing.

## 🗂️ Core Architecture & Implementation

### Models
- `App\Models\User`
- `App\Models\Admin`

### Key Routes
- **Authentication:**
  - `GET /register` - Livewire component (`App\Livewire\Auth\User\Register`) for user registration.
- **Social Login (Google):**
  - `GET /auth/google/redirect` - Redirects to Google OAuth.
  - `GET /auth/google/callback` - Handles the Google OAuth callback.
- **Onboarding:**
  - `GET /complete-profile` - Route for users to complete their profile after social login (protected by `EnsureGoogleSession` middleware).
  - `GET /verify-otp` - Route for OTP verification (protected by `EnsureGoogleSession` middleware).

### Excluded Directories
- `node_modules/` (Node dependencies)
- `vendor/` (Composer dependencies)

## 🚀 Setup Information
The project is configured to use SQLite by default for development (as per `database/database.sqlite` creation script in composer), but the README suggests MySQL.
To run the project locally, the standard Laravel workflow applies:
1. `composer install`
2. `npm install`
3. `npm run dev`
4. `php artisan serve`

## 🔧 Recent Fixes & Modifications
- **Google OAuth Fixes:**
  - Updated `.env` and `config/services.php` to use `GOOGLE_REDIRECT_URI` natively without the `/api` prefix to prevent `redirect_uri_mismatch` errors.
  - Set Guzzle client in `GoogleAuthController` to `['verify' => false]` strictly for local Windows dev bypassing cURL error 60 SSL certificate problems.
  - Added error logging to `storage/logs/laravel.log` on OAuth failure.
- **OTP Mailing System:**
  - Fixed `MAIL_ENCRYPTION` typo in `.env`.
  - Created `App\Mail\OtpMail` Mailable class.
  - Created simple HTML view for email at `resources/views/emails/otp.blade.php`.
- **Livewire Infrastructure:**
  - Added the required main layout file `resources/views/components/layouts/app.blade.php` to prevent Livewire full-page components from silently failing or crashing.
