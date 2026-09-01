<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
  <a href="https://github.com/Sudip-Magar/Sajha_Auction">
    <img src="https://img.shields.io/badge/status-active-success.svg" alt="Project Status">
  </a>
  <a href="https://opensource.org/licenses/MIT">
    <img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License">
  </a>
</p>

# 🏆 Sajha Auction

A modern **online auction platform** built with Laravel, Livewire, Alpine.js, and DaisyUI.  
This project provides a real-time bidding system with authentication, social login, and clean UI components.

---

## 🚀 Features

- 🔐 Authentication system (Laravel + Socialite)
- 🔥 Real-time interactive UI (Livewire + Alpine.js)
- 🎨 Modern UI (Tailwind CSS + DaisyUI + MaryUI)
- 📧 Email system (Laravel Mail)
- 👤 Social Login (Google / GitHub ready via Socialite)
- 🗂️ Auction listing and bidding system
- ⚡ Fast and reactive frontend experience

---

## 🛠️ Tech Stack

- **Backend:** Laravel
- **Frontend:** Livewire, Alpine.js
- **UI:** Tailwind CSS, DaisyUI, MaryUI
- **Auth:** Laravel Breeze / Socialite
- **Database:** MySQL
- **Build Tool:** Vite

---

## ⚙️ Installation Steps

### 1. Clone the repository

```bash
git clone https://github.com/Sudip-Magar/Sajha_Auction.git
cd Sajha_Auction
```

### 2. Install Following Package and Dependencies

```bash
composer install
composer require livewire/livewire
npm install
npm install alpinejs
composer require laravel/socialite
composer require illuminate/mail
```

### 3. Setup Environment file

```bash
1. copy .env.example file
2. paste it in root directory i.e inside Sajha_aution
3. renamed it to .env
4. add the following line inside the .env file
```

### 4. Configure .env file 
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sajha_auction
DB_USERNAME=your-username
DB_PASSWORD=your-password

GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=your-Oauth-url

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### 5. Additional configuration and migrate
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate
```

### 6. Run the project
```bash
npm run dev 
php artisan serve
```
