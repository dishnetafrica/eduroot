# EduRoot REST API — v1 Specification

**Base URL:** `https://your-school.in/api/v1`
**Format:** JSON only. All requests must send `Content-Type: application/json`.
**Auth:** JWT Bearer token — `Authorization: Bearer {token}`

---

## Table of contents

- [Authentication](#authentication)
- [Error format](#error-format)
- [Rate limits](#rate-limits)
- [Role permissions](#role-permissions)
- [Module: Students](#module-students)
- [Module: Fees](#module-fees)
- [Module: Attendance](#module-attendance)
- [Module: Exams & Results](#module-exams--results)
- [Module: Notifications](#module-notifications)
- [Module: AI layer](#module-ai-layer)
- [Implementation guide](#implementation-guide)

---

## Authentication

### JWT payload structure

Every JWT contains:
```json
{
  "sub": 42,
  "role": "student",
  "school_id": 1,
  "session_id": 3,
  "iat": 1743200000,
  "exp": 1743203600
}
```

`session_id` is the active academic year ID from `sessions.id`. All data queries in fee/attendance/exam endpoints are automatically scoped to this session — callers do not need to pass it explicitly.

---

### `POST /auth/login`

**Auth:** None (public)

**Request:**
```json
{
  "username": "rahul@school.in",
  "password": "plaintextpassword",
  "school_id": 1
}
```

`username` accepts either:
- Staff: email address (maps to `staff.email`)
- Student: admission number (maps to `students.admission_no`)
- `school_id` is required in SaaS mode (`saas_enabled = true`). Omit for single-school installs.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "dGhpcyBpcyBhIHJlZnJlc2g...",
    "expires_in": 3600,
    "user": {
      "id": 42,
      "name": "Rahul Sharma",
      "email": "rahul@school.in",
      "role": "student",
      "image_url": "https://school.in/uploads/students/rahul.jpg",
      "currency_symbol": "₹",
      "date_format": "d/m/Y",
      "lang_code": "hi"
    }
  }
}
```

**Errors:**
| Code | HTTP | Meaning |
|---|---|---|
| `INVALID_CREDENTIALS` | 401 | Wrong username or password |
| `ACCOUNT_DISABLED` | 403 | `is_active = 0` on users table |
| `SCHOOL_NOT_FOUND` | 404 | Invalid school_id in SaaS mode |
| `TOO_MANY_ATTEMPTS` | 429 | >5 failed attempts in 15 min |

---

### `POST /auth/refresh`

**Auth:** None (public)

**Request:**
```json
{ "refresh_token": "dGhpcyBpcyBhIHJlZnJlc2g..." }
```

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600
  }
}
```

Refresh tokens are valid for 30 days. On rotation, the old refresh token is invalidated. Store refresh tokens in `api_refresh_tokens` table (new table, see Implementation Guide).

---

### `POST /auth/logout`

**Auth:** Bearer token

Invalidates the refresh token. The access token expires naturally.

**Response 200:**
```json
{ "status": "success" }
```

---

## Error format

All errors follow the same envelope:
```json
{
  "status": "error",
  "code": "SNAKE_CASE_CODE",
  "message": "Human readable string (English)",
  "errors": { "field": "validation message" }
}
```

`errors` object is only present for 422 validation failures.

**Standard HTTP codes:**
| Code | When |
|---|---|
| 200 | Success |
| 201 | Created (POST that creates a resource) |
| 400 | Bad request / malformed JSON |
| 401 | Missing or expired token |
| 403 | Valid token but insufficient role |
| 404 | Resource not found |
| 422 | Validation failure (errors object included) |
| 429 | Rate limit exceeded |
| 500 | Server error (log internally, never expose details) |

---

## Rate limits

| Tier | Limit |
|---|---|
| Auth endpoints | 10 requests / minute per IP |
| AI endpoints | 20 requests / minute per school |
| All other endpoints | 120 requests / minute per token |

Exceeded limit returns 429 with headers:
```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1743203660
```

---

## Role permissions

| Endpoint group | superadmin | admin | teacher | accountant | student | parent | librarian |
|---|---|---|---|---|---|---|---|
| `GET /student/me` | — | — | — | — | ✅ | ✅ (own child) | — |
| `GET /students` | ✅ | ✅ | ✅ | ✅ | — | — | — |
| `GET /fees/balance` | ✅ | ✅ | — | ✅ | ✅ | ✅ | — |
| `GET /fees/history` | ✅ | ✅ | — | ✅ | ✅ | ✅ | — |
| `GET /fees/receipt/:id` | ✅ | ✅ | — | ✅ | ✅ | ✅ | — |
| `GET /attendance` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| `POST /attendance` | ✅ | ✅ | ✅ | — | — | — | — |
| `GET /exams` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| `GET /exams/:id/result` | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| `POST /notifications/send` | ✅ | ✅ | ✅ | ✅ | — | — | — |
| `POST /ai/report-card-remark` | ✅ | ✅ | ✅ | — | — | — | — |
| `GET /ai/fee-risk` | ✅ | ✅ | — | ✅ | — | — | — |

---

## Module: Students

### `GET /student/me`

Returns the authenticated student's own profile. If the JWT role is `parent`, returns the linked child's profile.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "id": 101,
    "admission_no": "ADM2024001",
    "firstname": "Rahul",
    "middlename": "",
    "lastname": "Sharma",
    "class": "Class 10",
    "section": "A",
    "roll_no": "15",
    "dob": "2009-05-12",
    "gender": "male",
    "mobileno": "9876543210",
    "email": "rahul@school.in",
    "guardian_name": "Suresh Sharma",
    "guardian_phone": "9998887776",
    "session": "2024-25",
    "student_session_id": 344,
    "image_url": "https://school.in/uploads/students/101.jpg",
    "school_house": "Blue House",
    "category": "General"
  }
}
```

`student_session_id` is the FK into `student_session.id` for the current academic year. Pass this when querying fees, attendance, and exam results on behalf of the student.

---

### `GET /students`

**Auth:** admin, teacher, accountant

**Query parameters:**
| Param | Type | Default | Description |
|---|---|---|---|
| `class_id` | int | — | Filter by `classes.id` |
| `section_id` | int | — | Filter by `sections.id` |
| `search` | string | — | Searches firstname, lastname, admission_no |
| `is_active` | yes \| no | yes | |
| `page` | int | 1 | 50 per page |

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 101,
      "admission_no": "ADM2024001",
      "name": "Rahul Sharma",
      "class": "Class 10",
      "section": "A",
      "roll_no": "15",
      "mobileno": "9876543210",
      "student_session_id": 344
    }
  ],
  "meta": { "total": 240, "page": 1, "per_page": 50, "total_pages": 5 }
}
```

---

### `GET /students/{id}`

Full student profile including documents and custom fields. Admin / teacher / accountant only.

---

## Module: Fees

### `GET /fees/balance`

The single most-called endpoint. Used by parent portal, WhatsApp bot, and accountant dashboard.

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `student_id` | int | Required for admin/accountant. Inferred from JWT for student/parent. |

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "student_id": 101,
    "student_name": "Rahul Sharma",
    "session": "2024-25",
    "currency_symbol": "₹",
    "total_due": 24000,
    "total_paid": 18000,
    "balance": 6000,
    "fee_groups": [
      {
        "fee_session_group_id": 8,
        "name": "Monthly Fee Pack",
        "due": 12000,
        "paid": 12000,
        "balance": 0,
        "next_due_date": null,
        "fee_types": [
          { "name": "Tuition Fee", "due": 9000, "paid": 9000 },
          { "name": "Library Fee", "due": 1800, "paid": 1800 },
          { "name": "Sports Fee",  "due": 1200, "paid": 1200 }
        ]
      },
      {
        "fee_session_group_id": 9,
        "name": "Annual Charges",
        "due": 12000,
        "paid": 6000,
        "balance": 6000,
        "next_due_date": "2025-04-10",
        "fee_types": [
          { "name": "Development Fee", "due": 8000, "paid": 4000 },
          { "name": "Exam Fee",        "due": 4000, "paid": 2000 }
        ]
      }
    ]
  }
}
```

> **Implementation note:** Balance is always computed live:
> ```sql
> SELECT sfm.amount AS due,
>        COALESCE(SUM(sf.amount - sf.amount_discount + sf.amount_fine), 0) AS paid,
>        sfm.amount - COALESCE(SUM(sf.amount - sf.amount_discount + sf.amount_fine), 0) AS balance
> FROM student_fees_master sfm
> LEFT JOIN student_fees sf ON sf.feemaster_id = sfm.id
> WHERE sfm.student_session_id = ?
> GROUP BY sfm.id
> ```
> Never cache this value.

---

### `GET /fees/history`

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `student_id` | int | Admin/accountant only |
| `from` | YYYY-MM-DD | Start date filter |
| `to` | YYYY-MM-DD | End date filter |

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 2201,
      "date": "2025-02-10",
      "amount": 6000,
      "amount_discount": 0,
      "amount_fine": 0,
      "payment_mode": "online",
      "fee_group": "Monthly Fee Pack",
      "receipt_no": "RCPT-2201",
      "receipt_url": "/api/v1/fees/receipt/2201",
      "collected_by": "Accountant Name"
    }
  ]
}
```

---

### `GET /fees/receipt/{payment_id}`

Returns the fee receipt PDF.

**Headers returned:**
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="RCPT-2201.pdf"
```

Uses the existing `print_headerfooter` template + `student_fees` data. Identical to the PDF the accountant prints from the admin panel — no new PDF logic needed.

---

## Module: Attendance

### `GET /attendance`

**Query parameters:**
| Param | Type | Default | Description |
|---|---|---|---|
| `student_id` | int | from JWT | Admin/teacher only |
| `month` | 1–12 | current | |
| `year` | YYYY | current | |
| `type` | class \| subject | class | Class or subject-level attendance |

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "student_id": 101,
    "student_name": "Rahul Sharma",
    "month": 3,
    "year": 2025,
    "summary": {
      "present": 18,
      "absent": 3,
      "late": 1,
      "half_day": 0,
      "holiday": 0,
      "total_working_days": 22,
      "attendance_pct": 86.4
    },
    "records": [
      { "date": "2025-03-01", "status": "present", "type_id": 1, "remark": null },
      { "date": "2025-03-04", "status": "absent",  "type_id": 3, "remark": "Sick" },
      { "date": "2025-03-05", "status": "late",    "type_id": 2, "remark": null }
    ]
  }
}
```

---

### `POST /attendance`

Mark attendance for an entire class section. One call per class per day.

**Request:**
```json
{
  "class_section_id": 12,
  "date": "2025-03-29",
  "records": [
    { "student_session_id": 344, "attendence_type_id": 1 },
    { "student_session_id": 345, "attendence_type_id": 3, "remark": "No information received" },
    { "student_session_id": 346, "attendence_type_id": 2 }
  ]
}
```

`attendence_type_id` values (from `attendence_type` table):
| ID | Status |
|---|---|
| 1 | Present |
| 2 | Late |
| 3 | Absent |
| 4 | Half day |
| 5 | Holiday |

**Response 201:**
```json
{
  "status": "success",
  "data": {
    "date": "2025-03-29",
    "class_section_id": 12,
    "marked": 45,
    "notifications_queued": 3
  }
}
```

After saving to `student_attendences`, the endpoint queues absent notifications if `notification_setting.student_absent_attendence = 1`. The notification cron picks these up and sends WhatsApp/SMS.

---

## Module: Exams & Results

### `GET /exams`

Returns exam groups the student is enrolled in. Only returns published groups for student/parent roles.

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `student_id` | int | Admin/teacher only |
| `session` | string | e.g. `2024-25`. Default: current. |

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 5,
      "name": "Unit Test 1",
      "exam_type": "basic_system",
      "session": "2024-25",
      "result_published": true,
      "result_url": "/api/v1/exams/5/result"
    },
    {
      "id": 6,
      "name": "Half Yearly",
      "exam_type": "school_grade_system",
      "session": "2024-25",
      "result_published": false
    }
  ]
}
```

---

### `GET /exams/{exam_group_id}/result`

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `student_id` | int | Admin/teacher only. Inferred from JWT for student/parent. |

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "exam_name": "Unit Test 1",
    "exam_type": "basic_system",
    "student_id": 101,
    "student_name": "Rahul Sharma",
    "admission_no": "ADM2024001",
    "class": "Class 10",
    "section": "A",
    "roll_no": "15",
    "total_marks": 250,
    "obtained_marks": 198,
    "percentage": 79.2,
    "grade": "B+",
    "rank": 8,
    "result": "pass",
    "subjects": [
      {
        "name": "Mathematics",
        "code": "MATH",
        "total_marks": 50,
        "obtained_marks": 44,
        "passing_marks": 17,
        "grade": "A",
        "attendance": "present",
        "note": null
      },
      {
        "name": "English",
        "code": "ENG",
        "total_marks": 50,
        "obtained_marks": 31,
        "passing_marks": 17,
        "grade": "C",
        "attendance": "present",
        "note": "Needs improvement in grammar"
      }
    ],
    "ai_remark": null
  }
}
```

`ai_remark` is `null` until `POST /ai/report-card-remark` is called for this student+exam combination. Once generated, it is returned here automatically.

---

## Module: Notifications

### `POST /notifications/send`

Send a notification to a student, a class section, or all students.

**Request:**
```json
{
  "event": "fee_submission",
  "channel": ["whatsapp", "sms", "push"],
  "target": {
    "type": "student",
    "student_id": 101
  },
  "vars": {
    "student_name": "Rahul Sharma",
    "amount": "₹6,000",
    "receipt_no": "RCPT-2201",
    "balance": "₹0"
  }
}
```

`target.type` options:
- `student` → requires `student_id`
- `class` → requires `class_section_id` (sends to all students in the class)
- `all` → sends to all active students in the school

`event` must match a key from `mailsms.php`. The `vars` object replaces `{placeholders}` in the SMS/email template.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "whatsapp": { "sent": true,  "to": "+91998887776" },
    "sms":      { "sent": true,  "to": "+91998887776" },
    "push":     { "sent": false, "reason": "no_fcm_token" }
  }
}
```

---

### `GET /notifications`

Notification inbox for the logged-in user.

**Query parameters:**
| Param | Type | Default |
|---|---|---|
| `is_read` | 0 \| 1 | — (all) |
| `page` | int | 1 |

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 901,
      "title": "Fee received",
      "message": "₹6,000 received for Rahul Sharma. Receipt: RCPT-2201",
      "type": "push",
      "is_read": false,
      "created_at": "2025-03-10T14:22:00+05:30"
    }
  ],
  "meta": { "unread_count": 3 }
}
```

---

### `POST /notifications/{id}/read`

Mark a notification as read. Updates `read_notification` table.

---

## Module: AI layer

These endpoints call the Claude API internally. They require the school to have an AI add-on enabled (stored in `addons` table).

### `POST /ai/report-card-remark`

Generate a personalised narrative paragraph for a student's exam result.

**Request:**
```json
{
  "exam_group_id": 5,
  "student_id": 101,
  "lang": "en",
  "tone": "encouraging",
  "force_regenerate": false
}
```

`lang`: `en`, `hi`, `gu`, `mr` — response is in this language.
`tone`: `encouraging` | `neutral` | `formal`
`force_regenerate`: if `true`, ignores cached remark and regenerates.

**Response 200:**
```json
{
  "status": "success",
  "data": {
    "student_id": 101,
    "exam_group_id": 5,
    "remark": "Rahul has shown strong performance in Mathematics and Science this term, scoring above 85% in both subjects. There is an opportunity to improve in English — consistent reading practice will make a meaningful difference. Overall, this is a solid result that reflects real effort.",
    "lang": "en",
    "cached": false,
    "generated_at": "2025-03-29T10:14:00Z"
  }
}
```

**Cost:** ~$0.001 per call. Stored in `ai_remarks(exam_group_id, student_id, lang, remark, generated_at)` — a new table to create.

---

### `GET /ai/fee-risk`

Weekly-computed list of students at risk of missing the next payment.

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `class_id` | int | Filter by class |
| `risk_min` | 0.0–1.0 | Minimum risk score. Default: 0.6 |
| `limit` | int | Default: 50 |

**Response 200:**
```json
{
  "status": "success",
  "data": [
    {
      "student_id": 312,
      "name": "Priya Patel",
      "class": "Class 7",
      "section": "B",
      "risk_score": 0.87,
      "risk_factors": [
        "Late 3 of last 4 months",
        "Attendance drop in March"
      ],
      "guardian_phone": "+91988...",
      "next_due_date": "2025-04-10",
      "balance": 4000,
      "suggested_action": "Call guardian before Apr 5"
    }
  ],
  "meta": { "computed_at": "2025-03-28T08:00:00Z" }
}
```

**How risk_score is computed (SQL-based, no ML needed):**
```sql
-- Risk factors, each adds weight:
-- +0.3  if avg_days_late (last 3 months) > 10
-- +0.25 if same month was missed in prior year
-- +0.2  if attendance_pct this month < 70
-- +0.15 if current balance > 50% of session total due
-- Capped at 1.0. Stored in fee_risk_cache table, refreshed weekly by cron.
```

---

## Implementation guide

### 1. New controller: `Api_v1.php`

Create `application/controllers/Api_v1.php` as the router. All `/api/v1/` routes point here via `routes.php`:

```php
$route['api/v1/(:any)'] = 'api_v1/index/$1';
```

The controller reads `$_SERVER['REQUEST_URI']` and `$_SERVER['REQUEST_METHOD']` to dispatch to handler methods. It never loads views — only `echo json_encode(...)`.

### 2. JWT middleware

Create `application/libraries/JwtMiddleware.php`. Call it at the top of every protected handler:

```php
$payload = $this->jwt_middleware->require_auth(['admin', 'teacher']);
$user_id = $payload->sub;
$role    = $payload->role;
$session_id = $payload->session_id;
```

Use the existing `application/third_party/jwt/` library. Sign with HS256. Store `JWT_SECRET` in `application/config/config.php` (not in database).

### 3. New tables needed

```sql
-- Refresh token store
CREATE TABLE api_refresh_tokens (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  token_hash    VARCHAR(64) NOT NULL,
  expires_at    DATETIME NOT NULL,
  revoked       TINYINT DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token_hash),
  INDEX idx_user (user_id)
);

-- AI remark cache
CREATE TABLE ai_remarks (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  exam_group_id   INT NOT NULL,
  student_id      INT NOT NULL,
  lang            VARCHAR(5) DEFAULT 'en',
  remark          TEXT NOT NULL,
  generated_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_exam_student_lang (exam_group_id, student_id, lang)
);

-- Fee risk cache (refreshed weekly by cron)
CREATE TABLE fee_risk_cache (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  student_id      INT NOT NULL,
  session_id      INT NOT NULL,
  risk_score      DECIMAL(4,3) NOT NULL,
  risk_factors    JSON,
  suggested_action TEXT,
  computed_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_student_session (student_id, session_id)
);
```

### 4. CORS headers

Add to `application/hooks/` a before-output hook that sends:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
```

### 5. Rate limiting

Store hit counts in `api_rate_limits(ip, endpoint_group, hits, window_start)`. Check at the top of `JwtMiddleware::require_auth()`. Cleanup old windows via cron daily.

### 6. WhatsApp bot integration (WhatsML)

The bot calls `/api/v1/auth/login` with a system account, gets a token, then calls `/api/v1/fees/balance?student_id={id}` or `/api/v1/attendance?student_id={id}` to answer parent queries.

Create a dedicated `role = 'bot'` user in `users` table with minimal permissions (read-only: fees + attendance + results). The bot's JWT will have `role = 'bot'` and the middleware enforces it cannot write.

### 7. Implementation order

| Week | Work |
|---|---|
| 1 | `Api_v1.php` skeleton + JWT middleware + auth endpoints |
| 2 | `/student/me` + `/students` |
| 3 | `/fees/balance` + `/fees/history` + `/fees/receipt` |
| 4 | `/attendance` GET + POST |
| 5 | `/exams` + `/exams/:id/result` |
| 6 | `/notifications/send` + `/notifications` GET |
| 7 | WhatsML integration test |
| 8–10 | AI endpoints (report-card-remark, fee-risk) |

### 8. Postman collection

Create `docs/api/eduroot-api.postman_collection.json` with:
- Environment variables: `base_url`, `token`, `student_id`
- Pre-request script on all protected folders: auto-refresh token if `expires_in` is past
- Example request + response for every endpoint

---

## Changelog

| Version | Date | Changes |
|---|---|---|
| v1.0.0 | 2025-03-29 | Initial spec |
