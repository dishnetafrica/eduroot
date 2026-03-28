# Schema: System & Addons

~39 tables covering addons/plugins, front CMS, print templates, system utilities, alumni, visitors, and complaints.

---

## Addons

### `addons`

Installed plugins/modules.

| Column | Notes |
|---|---|
| `id` | PK |
| `unique_identifier` | Slug, e.g. `transport`, `hostel`, `alumni` |
| `name` | Display name |
| `version` | Installed version |
| `is_active` | `1` / `0` |

### `addon_versions`

Version history and update log for each addon.

---

## Front CMS

The public school website is driven entirely by these tables.

### `front_cms_pages`

Web pages (About Us, Contact, Facilities).

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | |
| `slug` | URL slug |
| `is_active` | |

### `front_cms_page_contents`

Content blocks within a page (multiple sections per page).

### `front_cms_menus` / `front_cms_menu_items`

Navigation menu structure for the school website.

### `front_cms_media_gallery`

Photo gallery entries.

### `front_cms_programs` / `front_cms_program_photos`

Academic programs / courses showcased on the website.

### `front_cms_settings`

Homepage layout, school tagline, social media links, hero image.

---

## Print / template tables

### `print_headerfooter`

Header and footer HTML for printed documents (fee receipts, admit cards, certificates). Supports school logo, address, and custom HTML.

### `id_card`

Student ID card template. Supports placeholders: `{student_name}`, `{roll_no}`, `{class}`, `{section}`, `{photo}`, `{barcode}`.

### `staff_id_card`

Staff ID card template.

### `template_admitcards` / `template_marksheets`

See [05-exams.md](05-exams.md).

---

## Transfer certificate tables

### `transfer_certificate_settings`

TC layout and content configuration (which fields appear, school seal position, etc.).

### `transfer_certificate_fields`

Custom fields that appear on the TC (school-specific columns).

### `transfer_certificate_no`

Auto-increment sequence for TC serial numbers (same pattern as `fee_receipt_no`).

---

## Resume / staff profile tables

### `resume_settings_fields`

Which fields appear on the public staff profile / resume page.

### `resume_additional_fields_settings`

Extra fields added to staff profiles.

---

## `captcha`

CAPTCHA image challenge/response store for the login form.

---

## `logs`

Application error / debug log table. Written by the custom logging library.

---

## `filetypes`

Allowed upload file types registry.

---

## `google_drive_setting`

Google Drive API credentials for backup/upload integration.

---

## `payment_settings`

See [04-fees.md](04-fees.md) — gateway API keys per school.

---

## Alumni

### `alumni_students`

Students who have graduated / left.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_id` | → `students.id` |
| `passing_year` | YEAR |
| `graduation_class` | Last class studied |
| `is_active` | |

### `alumni_events`

Events organised for alumni.

---

## Visitors

### `visitors_book`

Visitor logbook.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Visitor name |
| `mobile`* | |
| `id_card`* | ID type + number |
| `meeting_with` | Staff name they came to meet |
| `purpose_id` | → `visitors_purpose.id` |
| `in_time` | DATETIME |
| `out_time`* | DATETIME |
| `note`* | |

### `visitors_purpose`

Lookup: Official, Personal, Delivery, Inspection, etc.

### `dispatch_receive`

Inbound/outbound dispatch register (letters, parcels, couriers).

| Column | Notes |
|---|---|
| `id` | PK |
| `type` | `receive` / `dispatch` |
| `from_to` | Sender or recipient |
| `reference_no`* | |
| `address`* | |
| `date` | DATE |
| `attachment`* | |
| `note`* | |

---

## Complaints

### `complaint`

Complaints submitted by students, parents, or staff.

| Column | Notes |
|---|---|
| `id` | PK |
| `complaint_type_id` | → `complaint_type.id` |
| `source` | `student` `parent` `staff` |
| `source_id` | ID of the source entity |
| `description` | TEXT |
| `status` | `open` `in_progress` `resolved` |
| `created_at` | |

### `complaint_type`

Lookup: Academic, Fee, Staff Behaviour, Infrastructure, etc.
