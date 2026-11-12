# LodgeMate — Full-Stack PG & Lodge Management System

LodgeMate is a complete **PHP Laravel 12 + MySQL** web application for managing Paying Guests (PGs), Hostels, and Lodges. It includes an **Owner Portal** and a **Student OTP Portal**, with Razorpay online payments, WhatsApp Business Cloud API integration, and PDF/Excel reporting.

---

## 1. Prerequisites (Windows)

- **XAMPP** (PHP 8.2+ and MySQL / MariaDB)
- **Composer** (v2.x)
- **MySQL Workbench** (optional, for visual database inspection)

---

## 2. Installation & Setup on Windows

1. **Start XAMPP Control Panel** and start **Apache** and **MySQL**.
2. **Create the Database** (via MySQL Workbench or phpMyAdmin):
   ```sql
   CREATE DATABASE lodgemate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. **Install Dependencies**:
   ```powershell
   cd d:\lodgemate
   composer install --ignore-platform-req=ext-gd
   ```
4. **Configure Environment**:
   ```powershell
   copy .env.example .env
   php artisan key:generate
   php artisan storage:link
   ```
   Verify `DB_DATABASE=lodgemate_db`, `DB_USERNAME=root`, `DB_PASSWORD=` in `.env`.
5. **Run Migrations & Seeders**:
   ```powershell
   php artisan migrate:fresh --seed
   ```
6. **Start the Development Server**:
   ```powershell
   php artisan serve
   ```
   Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

---

## 3. Demo Credentials

- **Owner Portal** (`/login`):
  - Email: `owner@lodgemate.com`
  - Password: `password`
- **Student Portal** (`/student/login`):
  - Mobile: `9000000001` (through `9000000008`)
  - OTP: Displayed on screen in `APP_DEBUG=true` local mode.

---

## 4. MySQL Workbench Setup

1. Open **MySQL Workbench** and create a new connection:
   - **Hostname**: `127.0.0.1`
   - **Port**: `3306`
   - **Username**: `root`
   - **Default Schema**: `lodgemate_db`
2. Connect and run `SHOW TABLES;` or open **Database → Reverse Engineer** to view the complete normalized ER diagram across all 19 tables (`users`, `properties`, `rooms`, `beds`, `students`, `rent_records`, `payments`, `payment_allocations`, `electricity_bills`, `electricity_allocations`, `security_deposits`, `deposit_transactions`, `expenses`, `complaints`, `complaint_attachments`, `notifications`, `announcements`, `otp_verifications`, `settings`).

---

## 5. External Integrations Configuration

### Razorpay Online Payments
1. Log in to [Razorpay Dashboard](https://dashboard.razorpay.com/) → **Account & Settings → API Keys**.
2. Generate Test/Live keys and add them to `.env`:
   ```env
   RAZORPAY_KEY_ID=rzp_test_xxxxxxxx
   RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxx
   RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
   ```
3. Point Razorpay Webhooks to `https://your-domain.com/webhooks/razorpay`.

### WhatsApp Business Cloud API
1. Create an app in [Meta for Developers](https://developers.facebook.com/) with WhatsApp Cloud API.
2. Add your Phone Number ID and Permanent Access Token in `.env` or inside **Owner Portal → Settings**.

---

## 6. Running Automated Tests

```powershell
php artisan test
```
