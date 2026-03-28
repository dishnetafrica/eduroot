# API v1 — Setup checklist

Drop these files into your EduRoot install and follow the steps below.

---

## Files to copy

```
application/
├── controllers/
│   └── Api_v1.php          ← main API controller
├── libraries/
│   └── JwtMiddleware.php   ← JWT create / verify middleware
└── models/
    └── Api_auth_model.php  ← refresh token store

docs/api/
├── api-spec-v1.md          ← full endpoint spec
├── migration-api-tables.sql← DB tables (run once)
└── SETUP.md                ← this file
```

---

## Step 1 — Run the migration

```bash
mysql -u your_user -p your_database < docs/api/migration-api-tables.sql
```

This creates 4 new tables:
- `api_refresh_tokens` — JWT refresh token store
- `ai_remarks` — AI-generated report card remark cache
- `fee_risk_cache` — weekly fee risk prediction cache
- `api_rate_limits` — per-IP rate limit tracking

---

## Step 2 — Add the JWT secret to config.php

Open `application/config/config.php` and add at the bottom:

```php
// ─── EduRoot API v1 ───────────────────────────────────────────
// JWT signing secret — minimum 32 characters, random, never commit
// Generate with: php -r "echo bin2hex(random_bytes(32));"
$config['jwt_secret'] = 'REPLACE_WITH_A_RANDOM_64_CHAR_STRING';
```

Generate a secure secret:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

## Step 3 — Add routes to routes.php

Open `application/config/routes.php` and add these lines **above** the existing routes:

```php
// ─── EduRoot REST API v1 ──────────────────────────────────────
$route['api/v1/(:any)'] = 'api_v1/route/$1';
// Note: CodeIgniter 3 does not support per-method routing natively.
// OPTIONS preflight is handled inside route() by checking REQUEST_METHOD.
```

---

## Step 4 — Verify .htaccess

Your `.htaccess` should already handle clean URLs via mod_rewrite.
The API routes work with the existing `.htaccess` — no changes needed.

---

## Step 5 — Test the login endpoint

```bash
curl -X POST https://your-school.in/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin@school.in","password":"yourpassword"}'
```

Expected response:
```json
{
  "status": "success",
  "data": {
    "token": "eyJ...",
    "refresh_token": "...",
    "expires_in": 3600,
    "user": { "id": 1, "name": "Admin", "role": "admin", ... }
  }
}
```

---

## Step 6 — Test an authenticated endpoint

```bash
TOKEN="eyJ..."   # from login response

curl https://your-school.in/api/v1/students \
  -H "Authorization: Bearer $TOKEN"
```

---

## Step 7 — Add cron job for refresh token cleanup

Add to crontab (runs daily at 2am):

```cron
0 2 * * * php /path/to/index.php cron fee_reminder
```

Add a cleanup call inside `application/controllers/Cron.php`:

```php
// In Cron::main() or a dedicated method:
$this->load->model('Api_auth_model');
$deleted = $this->api_auth_model->deleteExpired();
log_message('info', "API: cleaned {$deleted} expired refresh tokens");
```

---

## Phase 2 — Implementing the stub endpoints

The following endpoints currently return `501 Not Implemented`.
Implement them in `Api_v1.php` in this order:

1. `GET /api/v1/fees/balance` — uses `Studentfeemaster_model::getStudentFees()`
2. `GET /api/v1/fees/history` — uses `Studentfee_model`
3. `GET /api/v1/fees/receipt/{id}` — renders existing PDF library
4. `GET /api/v1/attendance` — uses `Stuattendence_model`
5. `POST /api/v1/attendance` — inserts into `student_attendences`
6. `GET /api/v1/exams` — uses `Examgroup_model`
7. `GET /api/v1/exams/{id}/result` — uses `Examresult_model::getStudentResultByExam()`
8. `POST /api/v1/notifications/send` — wraps existing `Mailsmsconf` library
9. `POST /api/v1/ai/report-card-remark` — calls Claude API, stores in `ai_remarks`
10. `GET /api/v1/ai/fee-risk` — reads from `fee_risk_cache`

See `docs/api/api-spec-v1.md` for the exact request/response shape of each endpoint.
