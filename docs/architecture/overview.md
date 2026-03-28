# Architecture Overview

EduRoot is a **CodeIgniter 3.x MVC application** served over Apache. This document covers the request lifecycle, folder structure, key conventions, and the patterns you will encounter in every file.

---

## Request lifecycle

```
Browser / Mobile
      |
   .htaccess  (mod_rewrite → everything hits index.php)
      |
   index.php  (sets ENVIRONMENT, loads CI bootstrap)
      |
   system/core/CodeIgniter.php
      |
   application/config/routes.php  (URI → Controller/Method)
      |
   Controller  (application/controllers/**/*.php)
      |  loads
   Model(s)  (application/models/*.php)  ←→  MySQL
      |  passes data to
   View  (application/views/**/*.php)
      |
   Response (HTML / JSON / PDF / CSV)
```

For AJAX calls the controller returns `json_encode(...)` directly.
For PDF generation the controller calls a PDF library and outputs binary.

---

## Folder structure

```
EduRoot/
│
├── index.php                   Entry point. Sets ENVIRONMENT.
├── .htaccess                   mod_rewrite + security headers.
│
├── application/
│   ├── config/                 All CI config files (database.php is gitignored).
│   │   ├── routes.php          URL routing table.
│   │   ├── autoload.php        Libraries/helpers loaded on every request.
│   │   ├── saas-config.php     Toggle: $config['saas_enabled'] = true/false.
│   │   └── mailsms.php         Notification event key registry.
│   │
│   ├── controllers/
│   │   ├── Site.php            Login, logout, password reset (all roles).
│   │   ├── Welcome.php         Front-end / CMS (public-facing pages).
│   │   ├── Cron.php            All scheduled jobs (fee reminders, alerts).
│   │   ├── Report.php          Report generation (attendance, finance, student).
│   │   ├── Student.php         Student-facing operations.
│   │   ├── Studentfee.php      Fee collection operations.
│   │   ├── Webhooks.php        Inbound webhook handlers (payment callbacks).
│   │   │
│   │   ├── admin/              110+ controllers for admin panel.
│   │   ├── user/               Student/parent portal.
│   │   │   └── gateway/        Per-gateway fee payment (28 gateways).
│   │   ├── onlineadmission/    Public admission + payment flow.
│   │   └── gateway_ins/        Installment payment gateways.
│   │
│   ├── models/                 155 models, one per entity/table group.
│   ├── views/                  PHP templates (admin, user, print, CMS).
│   ├── libraries/              Custom CI libraries (Customlib, etc.).
│   ├── language/               78 language packs.
│   └── third_party/
│       ├── PHPMailer/
│       ├── firebase/           FCM push notifications.
│       ├── jwt/                Token auth for mobile API.
│       ├── omnipay/            Payment gateway abstraction (needs composer install).
│       ├── midtrans/
│       ├── pesapal/
│       └── billplz/
│
├── backend/                    Static frontend assets (Bootstrap, JS, CSS).
├── system/                     CodeIgniter core — do not edit.
└── uploads/                    Runtime file uploads (gitignored).
```

---

## Key conventions

### Session scoping
Almost every data query is scoped to the **current academic session**:
```php
$this->current_session  // set in the base controller from sch_settings.session_id
```
If you're writing a new query and results look wrong, check whether you're missing `where('session_id', $this->current_session)`.

### Role-based access
Roles are checked via the `Customlib` library on every admin controller method. The role hierarchy is:

```
superadmin → admin → teacher → accountant → librarian → student → parent
```

Permissions are stored in `roles_permissions` (module-level) and checked via `module_permissions`.

### Autoloaded libraries
These are available in every controller without `$this->load->library()`:
- `email` — PHPMailer wrapper
- `session` — CI session
- `form_validation`
- `upload`
- `pagination`
- `Customlib` — the app's Swiss Army knife (user data, school settings, helper functions)

### CSRF
All POST forms must include `csrfField()`. Missing this = 403 on form submit.

### JSON responses
AJAX endpoints return:
```php
echo json_encode(['status' => 'success', 'data' => $data]);
// or
echo json_encode(['status' => 'error', 'message' => 'Something went wrong']);
```

### File uploads
Uploaded files land in `uploads/`. The path is stored relative to the uploads root (not absolute). Always use `base_url('uploads/' . $filename)` when building URLs.

### PDF generation
Fee receipts, marksheets, admit cards, and ID cards are generated via custom PDF libraries in `application/libraries/`. The controller calls the library and outputs with `Content-Type: application/pdf`.

---

## Environment

Set in `index.php`:

```php
define('ENVIRONMENT', 'production'); // development | testing | production
```

- `development` → errors displayed, full error_reporting
- `production` → errors suppressed, logged to `application/logs/`

---

## Database

MySQL with CodeIgniter Active Record. All queries go through `$this->db`.

Connection config lives in `application/config/database.php` (gitignored — copy from `database.example.php`).

Charset: `utf8mb4` — important for Indian language content in student names and addresses.

---

## Cron jobs

All scheduled logic lives in `application/controllers/Cron.php`. Routes map as `cron/(:any)` → `cron/index/$1`.

Key jobs:
| Route | Trigger | Purpose |
|---|---|---|
| `cron/fee_reminder` | Daily 8am | Send fee reminder SMS/email/WhatsApp |
| `cron/attendance_notification` | Daily 3pm | Send absent/present alerts |
| `cron/main` | Hourly | General housekeeping |

Schedule these via crontab using curl:
```bash
0 8 * * * curl -s https://your-domain.com/cron/fee_reminder
```
