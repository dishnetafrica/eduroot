# Schema: Core / Auth

17 tables that underpin authentication, permissions, school settings, and i18n.

---

## `users`

The single login table for all roles. Every human actor in the system has exactly one row here.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `username` | VARCHAR | Login username |
| `password` | VARCHAR | Bcrypt hash |
| `role` | ENUM | `superadmin` `admin` `teacher` `accountant` `librarian` `student` `parent` |
| `is_active` | TINYINT | `1` = active, `0` = disabled |
| `verification_code` | VARCHAR* | Password reset token |
| `lang_id` | INT* | → `languages.id` — per-user UI language |
| `currency_id` | INT* | → `currencies.id` |

**Notes:**
- `users.id` is linked to `students.id` and `staff.id` but they are separate tables — there is no FK constraint, the app joins them logically.
- `role` determines which portal the user lands on after login (handled in `Site.php::login()`).

---

## `roles`

Custom roles beyond the built-in seven. Used to create school-specific staff roles (e.g. "Vice Principal").

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `name` | VARCHAR | Role display name |
| `is_active` | TINYINT | |

---

## `roles_permissions` / `role_permissions`

Maps roles to permitted modules. There are two similar tables — `roles_permissions` is the primary one used by `Userpermission_model`.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `role_id` | INT | → `roles.id` |
| `permission` | VARCHAR | Module/action key string |
| `is_active` | TINYINT | |

---

## `module_permissions`

Granular per-module enable/disable, checked by `Customlib` before rendering any page.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `module` | VARCHAR | Module key (e.g. `transport`, `hostel`, `library`) |
| `is_active` | TINYINT | School-level toggle |

---

## `permission_group` / `permission_parent` / `permission_student`

Extended permission tables for specific role types. These override the base `roles_permissions` for the parent and student portals.

---

## `sessions`

Academic years. Every piece of school data is scoped to a session.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `session` | VARCHAR | e.g. `2024-25` |
| `is_active` | TINYINT | Only one session is active at a time |

**Critical:** `sch_settings.session_id` points to the currently active session. The app reads this into `$this->current_session` in the base controller. If you add a new data table that is session-scoped, always add a `session_id` column and filter by it.

---

## `sch_settings`

One row per school (or one row total in single-school mode). The application's master configuration.

Key columns (partial — this table has ~80+ columns):

| Column | Notes |
|---|---|
| `id` | PK |
| `session_id` | → `sessions.id` — the active academic year |
| `school_name` | |
| `school_code` | |
| `address` | |
| `phone` | |
| `email` | |
| `logo` | Path under `uploads/` |
| `currency` | Symbol, e.g. `₹` |
| `currency_name` | e.g. `INR` |
| `timezone` | PHP timezone string |
| `date_format` | e.g. `d/m/Y` |
| `lang` | Default language code |
| `saas_school_id`* | Tenant identifier in SaaS mode |
| `fee_receipt_prefix` | e.g. `RCPT-` |
| `front_side_whatsapp` | `1`/`0` — show WhatsApp link in footer |
| `front_side_whatsapp_mobile` | WhatsApp contact number |
| `whatsapp_api_*` | API endpoint and token for automated WA messages |
| `smtp_*` | Email config (host, port, user, pass, encryption) |
| `sms_*` | SMS gateway config |
| `fcm_key` | Firebase Cloud Messaging server key |

**Note:** A large portion of "settings" screens write to this table. When you add a new school-level config flag, add a column here.

---

## `userlog`

Login audit trail.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `user_id` | INT | → `users.id` |
| `date` | DATETIME | Login timestamp |
| `ip_address` | VARCHAR | |
| `browser` | VARCHAR | User agent string |

---

## `languages`

Supported UI languages.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | e.g. `Hindi`, `Gujarati` |
| `code` | e.g. `hi`, `gu` |
| `is_active` | School can disable languages they don't need |
| `is_rtl` | `1` for Arabic, Urdu etc. |

Language strings live in `application/language/{LanguageName}/` as PHP array files.

---

## `lang_keys` / `lang_pharses`

Dynamic language strings added via the admin panel (school-specific overrides on top of the bundled language files).

---

## `currencies`

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | e.g. `Indian Rupee` |
| `code` | `INR` |
| `symbol` | `₹` |
| `is_active` | |

---

## `sidebar_menus` / `sidebar_sub_menus`

Admin panel navigation structure. Controlled via `admin/Sidemenu.php`. Schools can reorder or hide menu items.
