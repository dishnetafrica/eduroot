# Schema: Student

24 tables covering student identity, enrolment per session, documents, custom fields, and the public admission flow.

---

## The core join you will write hundreds of times

```sql
SELECT s.*, ss.id AS student_session_id, c.class, sec.section
FROM students s
JOIN student_session ss ON ss.student_id = s.id
JOIN classes c ON c.id = ss.class_id
JOIN sections sec ON sec.id = ss.section_id
WHERE ss.session_id = {current_session}
  AND s.is_active = 'yes'
```

---

## `students`

Static personal and guardian data. Does not change between academic years.

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK |
| `admission_no` | VARCHAR | Unique admission number |
| `roll_no` | VARCHAR* | Class roll number |
| `admission_date` | DATE | |
| `firstname` | VARCHAR | |
| `middlename` | VARCHAR* | |
| `lastname` | VARCHAR | |
| `image` | VARCHAR* | Path under `uploads/` |
| `gender` | ENUM | `male` `female` `other` |
| `dob` | DATE | |
| `blood_group` | VARCHAR* | |
| `religion` | VARCHAR* | |
| `cast` | VARCHAR* | Caste category |
| `category_id` | INT* | → `categories.id` (SC/ST/OBC/General) |
| `mobileno` | VARCHAR* | Student's own mobile |
| `email` | VARCHAR* | |
| `state` | VARCHAR* | |
| `city` | VARCHAR* | |
| `pincode` | VARCHAR* | |
| `current_address` | TEXT* | |
| `permanent_address` | TEXT* | |
| `adhar_no` | VARCHAR* | Aadhaar number |
| `samagra_id` | VARCHAR* | MP govt. ID |
| `rte` | TINYINT | Right to Education flag |
| `bank_account_no` | VARCHAR* | |
| `bank_name` | VARCHAR* | |
| `ifsc_code` | VARCHAR* | |
| `father_name` | VARCHAR* | |
| `father_phone` | VARCHAR* | |
| `father_occupation` | VARCHAR* | |
| `father_pic` | VARCHAR* | |
| `mother_name` | VARCHAR* | |
| `mother_phone` | VARCHAR* | |
| `mother_occupation` | VARCHAR* | |
| `mother_pic` | VARCHAR* | |
| `guardian_name` | VARCHAR* | Active guardian |
| `guardian_relation` | VARCHAR* | |
| `guardian_phone` | VARCHAR* | Primary contact for notifications |
| `guardian_email` | VARCHAR* | |
| `guardian_address` | TEXT* | |
| `guardian_occupation` | VARCHAR* | |
| `guardian_pic` | VARCHAR* | |
| `guardian_is` | ENUM* | `father` `mother` `other` |
| `hostel_room_id` | INT* | → `hostel_rooms.id` |
| `school_house_id` | INT* | → `school_houses.id` |
| `height` | DECIMAL* | cm |
| `weight` | DECIMAL* | kg |
| `measurement_date` | DATE* | |
| `previous_school` | VARCHAR* | |
| `note` | TEXT* | Admin notes |
| `dis_note` | TEXT* | Disable reason |
| `dis_reason` | INT* | → `disable_reason.id` |
| `disable_at` | DATETIME* | |
| `is_active` | ENUM | `yes` / `no` |
| `app_key` | VARCHAR* | FCM token for student mobile app |
| `parent_app_key` | VARCHAR* | FCM token for parent mobile app |
| `parent_id` | INT* | → `users.id` of parent account |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

---

## `student_session`

One row per student per academic year. **The most joined table in the system.**

| Column | Type | Notes |
|---|---|---|
| `id` | INT | PK — this is `student_session_id` everywhere |
| `student_id` | INT | → `students.id` |
| `session_id` | INT | → `sessions.id` |
| `class_id` | INT | → `classes.id` |
| `section_id` | INT | → `sections.id` |
| `default_login` | TINYINT* | |

When a student is promoted to the next class at year-end, a new row is inserted here — the old row is retained for historical data.

---

## `categories`

Reservation/caste categories (General, SC, ST, OBC, EWS).

| Column | Notes |
|---|---|
| `id` | PK |
| `category` | Display name |

---

## `disable_reason`

Lookup table for reasons a student account can be disabled (TC issued, fee default, long absence, etc.).

---

## `school_houses`

House system (Red House, Blue House, etc.) for inter-house competitions.

---

## `student_doc`

Uploaded documents per student (birth certificate, transfer certificate, photos).

| Column | Notes |
|---|---|
| `id` | PK |
| `student_id` | → `students.id` |
| `title` | Document label |
| `file` | Path under `uploads/` |

---

## `student_educational_details`

Previous academic history (last school, percentage, year of passing).

---

## `student_sibling`

Links siblings within the same school (used for sibling fee discounts).

| Column | Notes |
|---|---|
| `id` | PK |
| `student_id` | → `students.id` |
| `sibling_student_id` | → `students.id` |

---

## `student_skills_detail` / `student_work_experience`

Portfolio fields for alumni or older students. Mostly unused in rural primary schools.

---

## `student_timeline`

Activity feed per student — admission, fee payment, result publish, etc. Displayed on the student profile page.

---

## `student_applyleave`

Leave applications submitted by students or parents.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `from_date` | DATE |
| `to_date` | DATE |
| `reason` | TEXT |
| `status` | `pending` `approve` `disapprove` |
| `apply_date` | DATE |

---

## `student_edit_fields` / `student_dashboard_settings`

School-level config for which student profile fields are visible/editable and what appears on the student dashboard.

---

## `student_refrence`

Student referral source tracking (how did this student find the school).

---

## Custom fields

The custom fields system lets schools add arbitrary data fields to student profiles without a schema change.

### `custom_fields`
Field definitions.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Field label |
| `type` | `text` `number` `dropdown` `date` |
| `is_required` | |
| `is_active` | |

### `custom_field_values`
Per-student values for custom fields.

| Column | Notes |
|---|---|
| `id` | PK |
| `custom_field_id` | → `custom_fields.id` |
| `student_id` | → `students.id` |
| `value` | TEXT — always stored as string |

---

## Online admission tables

These tables handle the **public-facing** admission form before a student is formally enrolled.

### `online_admissions`
Submitted admission forms.

| Column | Notes |
|---|---|
| `id` | PK |
| `firstname` / `lastname` | Applicant name |
| `dob` | DATE |
| `gender` | |
| `class_id` | Requested class |
| `status` | `pending` `approved` `rejected` |
| `payment_status` | `paid` `unpaid` |
| `form_no` | Auto-generated form number |
| `created_at` | |

### `online_admission_fields`
Extra fields defined by the school for their admission form.

### `online_admission_custom_field_value`
Submitted values for those extra fields.

### `online_admission_payment`
Payment record when admission form has a fee.

---

## `enquiry` / `enquiry_type`

Pre-admission enquiry tracking. Enquiries can be converted to `online_admissions` or directly to `students`.

### `enquiry`
| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Enquirer name |
| `contact_no` | |
| `email`* | |
| `class_id` | Interested class |
| `enquiry_type_id` | → `enquiry_type.id` |
| `reference` | How they heard about the school |
| `follow_up` | DATE — next follow-up date |
| `status` | `active` `converted` `closed` |
| `created_at` | |

### `enquiry_type`
Lookup: Walk-in, Phone, Website, Referral, etc.

---

## `reference`

Referral source lookup (used in student admission and enquiry forms).
