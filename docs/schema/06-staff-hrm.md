# Schema: Staff / HRM

20 tables covering staff profiles, roles, attendance, payroll, leave, and role-specific sub-tables for teachers, accountants, and librarians.

---

## `staff`

Core staff profile. Similar to `students` but for employees.

| Column | Notes |
|---|---|
| `id` | PK — same value as `users.id` for this staff member |
| `employee_id` | HR-assigned employee number |
| `name` | First name |
| `surname` | Last name |
| `dob` | DATE |
| `gender`* | `male` `female` `other` |
| `contact_no` | Primary phone |
| `email` | Work email |
| `department` | Department name (denormalised) |
| `designation` | Designation name (denormalised) |
| `is_active` | `yes` / `no` |
| `currency_id`* | → `currencies.id` |
| `lang_id`* | → `languages.id` — portal language |

**Note:** Department and designation are stored as strings here. The FK-linked versions are in `staff_designation` and `department` tables. Always join those tables for display — the denormalised columns go stale.

---

## `department`

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | `Science Dept`, `Administration` |

## `staff_designation` (also: `designation` table)

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | `Principal`, `PGT`, `TGT`, `PRT`, `Clerk` |

---

## `teachers`

Extended profile for staff with the `teacher` role. Linked to `staff.id`.

| Column | Notes |
|---|---|
| `id` | PK (= `staff.id`) |
| `qualification` | Highest qualification |
| `experience` | Years |
| `bio`* | Short description |
| `resume`* | Path to uploaded resume |
| `facebook`* | Social links |
| `twitter`* | |
| `linkedin`* | |

## `teacher_subjects`

Which subjects a teacher teaches across which class sections.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `class_section_id` | → `class_sections.id` |
| `subject_id` | → `subjects.id` |
| `session_id` | → `sessions.id` |

---

## `accountants`

Extended profile for accountant-role staff.

## `librarians`

Extended profile for librarian-role staff.

---

## `staff_roles`

Many-to-many: a staff member can have multiple roles.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `role_id` | → `roles.id` |

---

## Attendance tables

### `staff_attendance_type`

Lookup: Present, Absent, Late, Half Day, Holiday, Half Day 2nd Shift. Values are stored in `application/config/payroll.php`:
```php
'present' => 1, 'half_day' => 4, 'late' => 2,
'absent' => 3, 'holiday' => 5, 'half_day_second_shift' => 6
```

### `staff_attendance`

Daily attendance for each staff member.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `date` | DATE |
| `attendance_type_id` | → `staff_attendance_type.id` |
| `remark`* | |

### `staff_attendence_schedules`

Working day schedule definition (which days staff are expected to attend).

---

## Leave management

### `leave_types`

Leave categories (Casual Leave, Sick Leave, Earned Leave, etc.).

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | |
| `days` | Annual quota |
| `is_active` | |

### `staff_leave_request`

Leave application submitted by a staff member.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `leave_type_id` | → `leave_types.id` |
| `from_date` | DATE |
| `to_date` | DATE |
| `reason` | TEXT |
| `status` | `pending` `approve` `disapprove` |
| `apply_date` | DATE |

### `staff_leave_details`

Individual day records within an approved leave request (breaks the range into daily rows for attendance cross-reference).

---

## Payroll tables

### `staff_payroll`

Payroll configuration per staff member — salary structure.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `basic_salary` | DECIMAL |
| `contract_type` | `permanent` `probation` |
| `epf_no`* | Employee provident fund number |
| `bank_account_no`* | |
| `bank_name`* | |
| `ifsc_code`* | |

### `payslip_allowance`

Allowance/deduction line items attached to a staff member's payroll (HRA, TA, Medical, PF, etc.).

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_payroll_id` | → `staff_payroll.id` |
| `name` | `HRA` `PF` `Medical` |
| `type` | `allowance` `deduction` |
| `amount` | DECIMAL |
| `percent`* | If percentage-based on basic |

### `staff_payslip`

Generated payslip per staff per month.

| Column | Notes |
|---|---|
| `id` | PK |
| `staff_id` | → `staff.id` |
| `month` | `01`–`12` |
| `year` | YYYY |
| `basic_salary` | Snapshot at time of generation |
| `gross_salary` | basic + allowances |
| `total_deduction` | |
| `net_salary` | |
| `working_days` | |
| `present_days` | |
| `payment_status` | `paid` `unpaid` |
| `paid_date`* | DATE |
| `session_id` | |

---

## Other staff tables

### `staff_documents`

Uploaded HR documents (appointment letter, ID proof, qualifications).

### `staff_timeline`

Activity feed on staff profile (joining, promotion, leave, payslip).

### `staff_rating`

Performance rating records per staff member per session.

### `staff_id_card`

ID card template assignment and generation tracking for staff.
