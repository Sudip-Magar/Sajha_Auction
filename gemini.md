# Sajha Auction - Project Overview

## 🏆 Project Description
Sajha Auction is a modern online auction platform built to provide a real-time bidding system with authentication, social login, and clean UI components.

## 🛠️ Technology Stack
- **Backend:** Laravel (v13.0)
- **Programming Language:** PHP (^8.3)
- **Frontend Framework:** Livewire (v4.3)
- **UI Library:** Mary UI (v2.8) with DaisyUI
- **JavaScript:** Alpine.js (^3.15.12)
- **CSS Framework:** Tailwind CSS (v4.0.0)
- **Build Tool:** Vite (^8.0.0)
- **Database:** MySQL (Configured via `.env`)
- **Real-time:** Laravel Reverb (WebSockets)

## 📦 Key Dependencies
- `laravel/socialite` - For Google OAuth login.
- `laravel/reverb` - For real-time notifications and bidding.
- `robsontenorio/mary` - UI components (Modals, Tables, Forms).
- `laravel/boost` - Agentic development workflow.

## 🗂️ Core Architecture & Implementation

### Models & CRUDs
- **User:** Standard user management.
- **Admin:** Admin management with dedicated guard.
- **Category:** Redesigned with a premium "Inventory" feel. Features self-referencing relationships, stats cards, and robust image management (including removal/cleanup).
- **Product:** Comprehensive product management for Sellers. Supports both "Direct Sell" and "Auction" types. Requires Admin approval before becoming public.
- **Seller Requests:** Modern applicant card layout with approval workflow and real-time notifications.
- **Settings:** Tabbed global configuration for Admin and personal profile management for Users.

### Real-time Notifications (Reverb)
- **Admin Notifications:** `SellerRegisteredNotification` (new seller request) and `NewProductUploadedNotification` (new product for approval).
- **User Notifications:** `SellerApprovedNotification` (congratulations on becoming a seller).
- **Infrastructure:** `resources/views/layouts/admin.blade.php` and `resources/views/layouts/app.blade.php` listen on private channels via Echo and dispatch browser events for Livewire refreshing.

### Key Routes
- **Admin:** Dashboard, Category Setup, Seller Requests, Product Approval, System Settings.
- **User:** Dashboard, My Products (for sellers), Profile Settings.
- **Authentication:** Standard Laravel/Socialite/OTP routes.

## 🔧 Reverb WebSocket Troubleshooting
If notifications are not working, ensure:
1.  **Server is running:** `php artisan reverb:start`
2.  **Broadcasting Driver:** `BROADCAST_CONNECTION=reverb` in `.env`.
3.  **CSRF Protection:** Ensure `<meta name="csrf-token" content="{{ csrf_token() }}">` is in the `<head>` of your layouts (Fixed).
4.  **Local Dev Port:** Reverb runs on `8080`.
    - `.env` should have `REVERB_PORT=8080` and `VITE_REVERB_PORT=8080`.
    - Configurations have been updated to use `8080` as the default fallback (Fixed).
5.  **Local Dev Scheme:** Use `REVERB_SCHEME=http` and `VITE_REVERB_SCHEME=http`.
6.  **Vite Build:** Run `npm run dev` after changing any `.env` variables to re-bundle the Echo configuration.

## 🚀 Recent Fixes & Modifications
- **Category Image Management:** Added backend logic to delete old images from storage when categories are deleted or images are removed/replaced.
- **Seller Approval Notification:** Admins now automatically notify users when their seller status is approved.
- **Product System:** Full implementation of Product model, migrations, and User/Admin Livewire components for a multi-stage listing workflow (Upload -> Admin Review -> Live).
- **User Notifications:** Established real-time Echo listening on the user-side layout for system alerts.
- **WebSocket Auth Fix:** Added missing CSRF meta tags to layouts and updated Echo configuration to support secure private channel authentication.
