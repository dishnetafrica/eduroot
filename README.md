# EduRoot — School Management SaaS

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/CodeIgniter-3.x-EF4223?style=for-the-badge&logo=codeigniter" alt="CodeIgniter">
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/Languages-78-00C7B7?style=for-the-badge" alt="Languages">
  <img src="https://img.shields.io/badge/Payment%20Gateways-28%2B-F7931A?style=for-the-badge" alt="Gateways">
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" alt="License">
</p>

> A comprehensive school management platform targeting rural and semi-urban schools across India — built to run as a white-label SaaS or single-school self-hosted installation.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [User Roles](#user-roles)
- [Payment Gateways](#payment-gateways)
- [Notification Channels](#notification-channels)
- [Multi-Language Support](#multi-language-support)
- [SaaS Mode](#saas-mode)
- [Cron Jobs](#cron-jobs)
- [API & Webhooks](#api--webhooks)
- [Security Notes](#security-notes)
- [Contributing](#contributing)

---

## Overview

EduRoot is a full-featured **school ERP and SaaS platform** built on CodeIgniter 3.x. It covers the complete lifecycle of a school — from student admission and fee collection through exam management, payroll, transport, and library — delivered through a clean role-based interface for admins, teachers, students, parents, accountants, and librarians.

Key differentiators:
- **WhatsApp-first notifications** — fee reminders, exam results, attendance alerts via WhatsApp link and API integrations
- **28+ payment gateways** — works out of the box for India, Africa, Southeast Asia, and global markets
- **78 UI languages** — ship to any market without code changes
- **SaaS-ready architecture** — single codebase, per-school tenancy toggle

---

## Features

### 🎓 Student Management
- Online and offline admission with custom fields
- Student ID card generation
- Transfer certificate and alumni management
- Parent portal linkage
- Batch / class / section management
- Student disable/enable with reason tracking

### 💰 Fee Management
- Fee types, categories, fee groups, and fee masters
- Fee discounts and waivers
- Balance fee tracking and reminders
- Fee forwarding between sessions
- Online fee payment (28+ gateways)
- PDF fee receipts with email/WhatsApp delivery
- Group fee submission

### 📝 Exam & Results
- Exam groups, schedules, and timetables
- Multiple grading systems: Basic, School Grade, College Grade, GPA, Average Passing
- Online examination with question bank
- Result publishing and CBSE marksheets
- Admit card generation
- Exam result notifications via email, SMS, WhatsApp

### 📅 Attendance
- Student attendance (class-wise and subject-wise)
- Staff attendance with half-day / late marking
- Biometric device integration
- Attendance reports with absent/present WhatsApp alerts

### 📚 Academic
- Timetable builder
- Homework assignment and evaluation
- Syllabus management
- Subject and batch-subject management
- Online classes / video tutorial management

### 🚌 Transport
- Vehicle and route management
- Pickup point assignment per student
- Transport fee integration

### 🏛️ Library
- Book catalog and issue management
- Member management

### 🏠 Hostel
- Room type and room management
- Hostel fee integration

### 👨‍💼 HRM & Payroll
- Staff profiles with department and designation
- Payroll with permanent / probation contract types
- Leave management and approval workflow
- Payslip generation
- Staff ID card generation
- Staff attendance reports

### 💬 Communication
- Email (SMTP via PHPMailer)
- SMS gateway integration
- WhatsApp link and API notifications
- Firebase push notifications
- In-app chat system
- Timeline / announcements
- Visitor management and dispatch

### 🌐 Front Office & CMS
- School website CMS (pages, blog, programs)
- Online admission form (public-facing)
- Online exam result lookup
- Annual calendar
- Front theme customisation

### ⚙️ Administration
- Role-based access control with granular module permissions
- Custom fields for students and staff
- Multi-currency support
- Addon / plugin system
- Audit log
- System settings and school profile
- Session management (academic year)
- Certificate template builder
- Report builder (attendance, finance, student)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | CodeIgniter 3.x (PHP) |
| Language | PHP 7.4+ |
| Database | MySQL 5.7+ |
| ORM / Query | CodeIgniter Active Record |
| Migrations | CI Migration Library |
| Frontend | Bootstrap 3 / AdminLTE |
| PDF Generation | Custom PDF libraries (fee receipts, marksheets, ID cards) |
| Email | PHPMailer |
| Push Notifications | Firebase Cloud Messaging |
| Auth Tokens | JWT |
| Payments | Omnipay + native gateway controllers |
| Sessions | CI Session (file/database driver) |
| Caching | CodeIgniter Cache (file/Memcached) |

---

## Project Structure

```
EduRoot-main/
├── application/
│   ├── config/              # All CI config files
│   │   ├── app-config.php   # App-level constants (MIME types, exam systems)
│   │   ├── saas-config.php  # SaaS mode toggle
│   │   ├── mailsms.php      # Notification event keys
│   │   ├── payroll.php      # Payroll/HR enums
│   │   └── routes.php       # URL routing
│   │
│   ├── controllers/
│   │   ├── admin/           # 110+ admin controllers (core ERP modules)
│   │   ├── user/            # Student/parent portal controllers
│   │   │   └── gateway/     # Per-gateway fee payment controllers
│   │   ├── onlineadmission/ # Public admission + payment flow
│   │   │   └── (28 gateway controllers)
│   │   ├── gateway_ins/     # Installment payment gateways
│   │   ├── Site.php         # Auth, login, password reset
│   │   ├── Cron.php         # All scheduled job logic
│   │   ├── Welcome.php      # Front-end / CMS
│   │   ├── Report.php       # Report generation
│   │   ├── Student.php      # Student-facing operations
│   │   ├── Studentfee.php   # Fee collection operations
│   │   ├── Financereports.php
│   │   └── Webhooks.php     # Inbound webhook handlers
│   │
│   ├── models/              # 155 models covering all data entities
│   ├── views/               # Blade-like PHP views (admin, user, print, CMS)
│   ├── libraries/           # Custom CI libraries
│   ├── language/            # 78 language packs
│   └── third_party/
│       ├── PHPMailer/
│       ├── firebase/
│       ├── jwt/
│       ├── omnipay/
│       ├── midtrans/
│       ├── pesapal/
│       └── billplz/
│
├── backend/                 # Frontend static assets
│   ├── bootstrap/
│   ├── js/
│   ├── custom/
│   ├── fullcalendar/
│   └── report/
│
├── system/                  # CodeIgniter core (do not edit)
├── uploads/                 # Runtime school uploads (gitignored)
├── index.php                # Application entry point
└── .htaccess                # URL rewriting + security headers
```

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 7.4 or higher |
| MySQL | 5.7 or higher |
| Apache | 2.4+ with `mod_rewrite` enabled |
| PHP Extensions | `pdo`, `pdo_mysql`, `mysqli`, `gd`, `mbstring`, `curl`, `openssl`, `zip` |
| Composer | 2.x (for Omnipay payment library) |
| Disk Space | 500 MB minimum |
| RAM | 512 MB minimum (1 GB+ recommended) |

---

## Installation

### 1. Clone / Extract

```bash
git clone https://github.com/your-org/eduroot.git
cd eduroot
```

### 2. Install PHP Dependencies

```bash
cd application/third_party/omnipay
composer install
cd ../../..
```

### 3. Database Setup

Create a MySQL database and import the schema:

```sql
CREATE DATABASE eduroot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then visit `http://your-domain.com/install` to run the web installer.

> **Note:** If the install route is disabled, import the SQL dump manually and proceed to step 4.

### 4. Database Configuration

Copy the example config and fill in your credentials:

```bash
cp application/config/database.example.php application/config/database.php
```

```php
// application/config/database.php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    'database' => 'eduroot',
    'dbdriver' => 'mysqli',
    'char_set' => 'utf8mb4',
    'dbcollat'  => 'utf8mb4_unicode_ci',
    // ...
);
```

### 5. Base URL

Edit `application/config/config.php`:

```php
$config['base_url'] = 'https://your-domain.com/';
```

### 6. File Permissions

```bash
chmod -R 755 application/logs/
chmod -R 755 application/cache/
chmod -R 755 application/tmp/
chmod -R 755 uploads/
```

### 7. Run Migrations

Navigate to `http://your-domain.com/migrate` (admin only) or use the Migrate controller to run all pending migrations.

---

## Configuration

### Email / SMTP

In **Admin → Settings → Email Configuration**, set your SMTP host, port, username, password, and encryption (OFF / SSL / TLS).

All 28 notification event types (fee submission, exam results, absent alerts, login credentials, etc.) can be toggled independently.

### SMS Gateway

Configured via **Admin → Settings → SMS Configuration**. Multiple SMS providers are supported through the SMS adapter layer.

### WhatsApp

Configure in **Admin → Settings → WhatsApp Settings**:
- Enable WhatsApp link in the front-site footer
- Set the support mobile number
- Connect your WhatsApp API endpoint for automated notifications (fee reminders, attendance, results)

### Firebase Push Notifications

Place your Firebase service account key at:
```
application/third_party/firebase_notification_key.json
```

### Environment

Set the environment in `index.php`:

```php
define('ENVIRONMENT', 'production'); // development | testing | production
```

---

## User Roles

| Role | Portal URL | Capabilities |
|---|---|---|
| **Super Admin** | `/admin` | Full system access, school setup, addons, billing |
| **School Admin** | `/admin` | All school modules, staff/student management |
| **Teacher** | `/teacher` | Timetable, homework, attendance, marks, exams |
| **Student** | `/student` | Fees, homework, results, attendance, timetable |
| **Parent** | `/parent` | Child's fees, attendance, results, communication |
| **Accountant** | `/accountant` | Fee collection, finance reports, expense tracking |
| **Librarian** | `/librarian` | Book catalog, issue/return management |

Role permissions are granular — each module access can be enabled/disabled per role via **Admin → Roles**.

---

## Payment Gateways

EduRoot ships with **28+ payment gateway integrations** covering India, Africa, Southeast Asia, and global markets:

| Region | Gateways |
|---|---|
| **India** | Razorpay, Stripe, PayU, Paytm, Instamojo, Cashfree, CCAvenue |
| **Africa** | Flutterwave, Paystack, iPay Africa, Pesapal, MomoPay, Ihela, Kowri, DpoPay |
| **Southeast Asia** | Midtrans, Toyyibpay, Billplz, JazzCash, Onepay |
| **Global** | PayPal, Stripe, Skrill, Mollie, 2Checkout, SSLCommerz, PayFast, PayHere, Checkout.com, Walkingm |

All gateways are available for both **regular fee payments** and **online admission** flows. New gateways can be added by creating a controller in `application/controllers/user/gateway/` and `application/controllers/onlineadmission/`.

---

## Notification Channels

Notifications are triggered automatically for the following events:

| Event | Email | SMS | WhatsApp | Push |
|---|---|---|---|---|
| Student admission | ✅ | ✅ | ✅ | ✅ |
| Fee submission | ✅ | ✅ | ✅ | — |
| Fee reminder | ✅ | ✅ | ✅ | — |
| Exam result published | ✅ | ✅ | ✅ | ✅ |
| Student absent | ✅ | ✅ | ✅ | ✅ |
| Staff absent | ✅ | ✅ | — | — |
| Homework assigned | ✅ | ✅ | ✅ | — |
| Online class / meeting | ✅ | ✅ | ✅ | ✅ |
| Login credentials | ✅ | ✅ | — | — |
| Online admission submitted | ✅ | ✅ | — | — |
| Alumni promotion | ✅ | ✅ | — | — |

All notification templates are translatable and configurable per school.

---

## Multi-Language Support

EduRoot ships with **78 language packs**. Language selection is per-school and per-user. Languages include:

Afrikaans, Albanian, Amharic, Arabic, Azerbaijani, Basque, Bengali, Bosnian, Catalan, Cebuano, Chinese, Croatian, Czech, Danish, Dutch, English, Esperanto, Estonian, Finnish, French, and 58 more.

To add a new language:
1. Copy `application/language/English/` to `application/language/YourLanguage/`
2. Translate all string values in the language files
3. Add the language to the school settings dropdown

---

## SaaS Mode

EduRoot supports multi-tenant SaaS deployment. Enable it in:

```php
// application/config/saas-config.php
$config['saas_enabled'] = true;
```

In SaaS mode:
- Each school gets its own subdomain or path-based tenancy
- Super Admin manages school onboarding, billing, and addon access
- Schools are isolated at the data level

For single-school self-hosted deployment, leave `saas_enabled = false`.

---

## Cron Jobs

Add the following cron jobs to your server's crontab:

```bash
# Fee reminders — runs daily
0 8 * * * curl -s https://your-domain.com/cron/fee_reminder > /dev/null

# Attendance SMS/WhatsApp alerts — runs daily after school hours
0 15 * * * curl -s https://your-domain.com/cron/attendance_notification > /dev/null

# General system cron — runs every hour
0 * * * * curl -s https://your-domain.com/cron/main > /dev/null
```

All cron logic lives in `application/controllers/Cron.php`. Routes follow `cron/(:any)` → `cron/index/$1`.

---

## API & Webhooks

### Webhooks

Inbound webhooks are handled by `application/controllers/Webhooks.php`. Payment gateways post callbacks to:

```
POST /webhooks/{gateway_name}
```

### Mobile API

A student/parent mobile API is available for building native apps. JWT is used for token-based authentication (`application/third_party/jwt/`).

### Token-based Downloads

Secure token links are available for:
- Fee receipts: `/download-receipt/{token}`
- CBSE marksheets: `/download-marksheet/{token}`
- Exam marksheets: `/download-exam-marksheet/{token}`

---

## Security Notes

- `application/config/database.php` is gitignored — **never commit credentials**
- All phone-home / licence-check URLs have been neutralised in `constants.php`
- `application/third_party/omnipay/vendor/` is gitignored — restore with `composer install`
- Runtime files (`logs/`, `cache/`, `tmp/`, `uploads/`) are gitignored
- The `.htaccess` blocks direct access to `application/` and `system/` directories
- Use HTTPS in production and set `$config['cookie_secure'] = TRUE` in `config.php`
- Rotate the `$config['encryption_key']` in `config.php` before going live

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Follow CodeIgniter 3 MVC conventions
4. Never commit `database.php`, `.env`, or school `uploads/`
5. Test migrations before opening a PR — migrations are irreversible on production data
6. Open a Pull Request with a clear description of the change

---

## License

Proprietary. See `application/config/license.php` for terms.

---

<p align="center">Built with ❤️ for schools across India &nbsp;|&nbsp; <a href="https://eduroot.in">eduroot.in</a></p>
