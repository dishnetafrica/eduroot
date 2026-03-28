# System Architecture

## Overview

EduRoot is built on **CodeIgniter 3.x**, a classic PHP MVC framework. The application follows a strict Model-View-Controller pattern with role-based controller namespacing.

---

## Request Lifecycle

```
Browser / API Client
        │
        ▼
   .htaccess  ──────────►  index.php
        │                       │
        │               Environment check
        │               Load CI core
        │                       │
        ▼                       ▼
   URL Router         application/config/routes.php
        │
        ▼
   Controller (namespaced by role)
    admin/       Teacher/       user/
    parent/      accountant/    librarian/
        │
        ├──► Model(s) ──► MySQL (via CI Active Record)
        │
        ├──► Libraries (email, upload, session, form_validation)
        │
        └──► View  ──► Layout ──► Browser
```

---

## Folder Structure

```
EduRoot-main/
│
├── index.php                         # Entry point. Sets ENVIRONMENT.
├── .htaccess                         # URL rewriting + directory protection
│
├── application/
│   │
│   ├── config/
│   │   ├── config.php                # Base URL, encryption key, session, cookies
│   │   ├── database.php              # DB credentials (gitignored — never commit)
│   │   ├── routes.php                # URL → controller mapping
│   │   ├── autoload.php              # Auto-loaded libraries, helpers, models
│   │   ├── app-config.php            # App constants: image sizes, exam types, MIME types
│   │   ├── saas-config.php           # saas_enabled toggle
│   │   ├── mailsms.php               # Notification event key registry
│   │   ├── payroll.php               # HR enums (attendance types, contract types)
│   │   ├── constants.php             # System constants, file permission modes
│   │   └── ss-constants.php          # Simple CRUD message constants
│   │
│   ├── controllers/
│   │   ├── admin/                    # 110+ controllers — school admin role
│   │   │   └── front/                # CMS sub-controllers (Banner, Events, Gallery…)
│   │   ├── user/                     # Student/parent portal
│   │   │   └── gateway/              # Per-gateway fee payment (28 gateways)
│   │   ├── onlineadmission/          # Public admission flow + 28 payment gateways
│   │   ├── gateway_ins/              # Instalment payment gateways
│   │   ├── teacher/                  # Teacher portal
│   │   ├── parent/                   # Parent portal
│   │   ├── accountant/               # Accountant portal
│   │   ├── librarian/                # Librarian portal
│   │   ├── Site.php                  # Auth: login, logout, password reset, 2FA
│   │   ├── Welcome.php               # Front-end / school website CMS
│   │   ├── Student.php               # Student CRUD (3,626 lines — split candidate)
│   │   ├── Studentfee.php            # Fee collection
│   │   ├── Report.php                # Report generation (120KB)
│   │   ├── Cron.php                  # All scheduled job logic
│   │   ├── Financereports.php        # Finance reporting
│   │   ├── Attendencereports.php     # Attendance reporting
│   │   └── Webhooks.php              # Inbound webhook handlers
│   │
│   ├── models/                       # 155 models — one per entity/table group
│   │
│   ├── views/
│   │   ├── admin/                    # Admin panel views (per-module subfolders)
│   │   ├── layout/                   # Master layouts per role (header, sidebar, footer)
│   │   │   ├── header.php            # Admin header
│   │   │   └── student/header.php    # Student portal header
│   │   ├── partial/                  # Reusable partials (pagination, alerts)
│   │   ├── print/                    # Print-only views (fee receipts, marksheets)
│   │   ├── front/                    # School website front-end views
│   │   ├── themes/                   # CMS theme files
│   │   ├── setting/                  # Settings views (email, SMS, WhatsApp…)
│   │   └── onlineadmission/          # Public admission form views
│   │
│   ├── libraries/
│   │   └── PHPMailer/                # Email library
│   │
│   ├── language/                     # 78 language packs (one folder per language)
│   │   └── English/                  # Default — all other languages mirror this
│   │
│   └── third_party/
│       ├── PHPMailer/                # SMTP email
│       ├── firebase/                 # Push notification key + jwt
│       ├── jwt/                      # JSON Web Token library
│       ├── omnipay/                  # Payment abstraction layer
│       ├── midtrans/                 # Midtrans SDK (Indonesia)
│       ├── pesapal/                  # Pesapal SDK (Africa)
│       └── billplz/                  # Billplz SDK (Malaysia)
│
├── backend/                          # Frontend static assets (served directly)
│   ├── bootstrap/                    # Bootstrap 3
│   ├── js/                           # Global JS (jQuery, DataTables, plugins)
│   ├── custom/                       # App-specific JS and CSS
│   ├── fullcalendar/                 # FullCalendar library
│   ├── report/                       # Report-specific assets
│   └── pdf_style.css                 # PDF print stylesheet
│
├── system/                           # CodeIgniter core — DO NOT EDIT
│
└── uploads/                          # School runtime files (gitignored)
    └── index.html                    # Prevents directory listing
```

---

## Controller Namespacing by Role

Each user role has its own controller namespace. A URL like `/admin/staff/index` maps to `application/controllers/admin/Staff.php → index()`. Role separation is enforced at the controller level via session checks.

| URL prefix | Controller folder | Session role required |
|---|---|---|
| `/admin/` | `controllers/admin/` | admin |
| `/teacher/` | `controllers/teacher/` | teacher |
| `/student/` | `controllers/user/` | student |
| `/parent/` | `controllers/parent/` | parent |
| `/accountant/` | `controllers/accountant/` | accountant |
| `/librarian/` | `controllers/librarian/` | librarian |
| `/` (root) | `controllers/` | public (no session) |

---

## Key Shared Libraries

| Library | Purpose | Auto-loaded |
|---|---|---|
| `session` | CI session (file driver by default) | Yes |
| `email` | PHPMailer wrapper | Yes |
| `form_validation` | Input validation | Yes |
| `upload` | File upload handling | Yes |
| `pagination` | Paginated list views | Yes |
| `Customlib` | App-specific helper functions | Yes |

---

## Auto-loaded Helpers

See `application/config/autoload.php`. Standard CI helpers active: `url`, `form`, `html`, `file`, `string`.

---

## Session Structure

Sessions are stored in the file system (or database if configured). Key session variables:

| Key | Description |
|---|---|
| `user_id` | Logged-in user's ID |
| `user_type` | Role: admin / teacher / student / parent / accountant / librarian |
| `school_id` | Active school (relevant in SaaS mode) |
| `session_id` | Active academic year/session |
| `lang` | Active UI language code |

---

## Environment

Set in `index.php`:

```php
define('ENVIRONMENT', 'production'); // or 'development' | 'testing'
```

- `development` — full error output, backtrace
- `production` — errors suppressed, logged only

---

## Notes on Large Files

Several controller files are very large and are candidates for splitting in a future refactor:

| File | Lines | Recommended split |
|---|---|---|
| `controllers/Student.php` | 3,626 | StudentAdmission, StudentAttendance, StudentFee, StudentProfile |
| `controllers/Report.php` | ~3,000 | AttendanceReport, FeeReport, ExamReport |
| `controllers/admin/Welcome.php` | ~2,200 | DashboardController + per-module |
| `controllers/admin/Schsettings.php` | ~2,000 | Per-settings-section controllers |
| `controllers/Studentfee.php` | ~1,900 | FeeCollect, FeeReport, FeeDiscount |
