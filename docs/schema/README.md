# Schema Reference — Index

EduRoot's database has **~211 tables** across 17 functional domains. This index maps domains to their schema doc and shows the key relationships between domains.

---

## Domain index

| # | Domain | Tables | Schema doc |
|---|---|---|---|
| 1 | Core / Auth | 17 | [01-core-auth.md](01-core-auth.md) |
| 2 | Student | 24 | [02-student.md](02-student.md) |
| 3 | Academic / Classes | 25 | [03-academic.md](03-academic.md) |
| 4 | Fees | 26 | [04-fees.md](04-fees.md) |
| 5 | Exams & Results | 23 | [05-exams.md](05-exams.md) |
| 6 | Staff / HRM | 20 | [06-staff-hrm.md](06-staff-hrm.md) |
| 7 | Homework & Content | 16 | [07-content.md](07-content.md) |
| 8 | Transport | 5 | [08-transport.md](08-transport.md) |
| 9 | Library & Hostel | 6 | [09-library-hostel.md](09-library-hostel.md) |
| 10 | Notifications | 10 | [10-notifications.md](10-notifications.md) |
| 11 | System & Addons | ~39 | [11-system.md](11-system.md) |

---

## The spine of the data model

Everything in EduRoot anchors to three tables. Understand these and the rest falls into place.

```
sessions          — academic year (e.g. "2024-25")
    |
    └── student_session   — a student's enrolment in a class/section for a given year
            |
            ├── students              — student's personal/guardian data (static)
            ├── classes               — class (Grade 1 / Class 10)
            ├── sections              — section within a class (A / B)
            │
            ├── student_fees_master   ← fees owed this session
            ├── student_fees          ← fee payments made
            ├── student_attendences   ← daily attendance records
            └── exam_group_class_batch_exam_students  ← exam entries
```

The `student_session.id` (aliased as `student_session_id`) is the single most important foreign key in the database. It appears in ~40 tables.

---

## Cross-domain foreign keys (the important joins)

```
users.id
  ├── students.id (login account for student portal)
  ├── staff.id    (login account for teacher/accountant portal)
  └── userlog.user_id

sessions.id (academic year)
  ├── student_session.session_id
  ├── feemasters.session_id
  ├── expenses.session_id
  └── exam_groups (implicit, via class_batch)

student_session.id
  ├── student_fees_master.student_session_id
  ├── student_fees.student_session_id
  ├── student_attendences.student_session_id
  ├── student_applyleave.student_session_id
  └── exam_group_class_batch_exam_students.student_session_id

class_sections.id  (class + section pair)
  ├── class_teacher.class_section_id
  ├── class_batches.class_section_id
  ├── timetables.class_section_id
  └── student_session (via class_id + section_id)

fee_groups.id
  ├── fee_groups_feetype.fee_group_id
  └── fee_session_groups.fee_group_id

exam_group_class_batch_exams.id
  ├── exam_group_class_batch_exam_subjects.egcbe_id
  ├── exam_group_class_batch_exam_students.egcbe_id
  └── exam_group_exam_results.exam_group_class_batch_exam_subject_id
```

---

## Naming conventions in the schema

| Pattern | Meaning |
|---|---|
| `student_*` | Data owned by / about a student |
| `staff_*` | Data owned by / about a staff member |
| `exam_group_*` | Part of the exam module's nested structure |
| `fee_*` / `fees_*` | Fee configuration tables |
| `student_fees_*` | Fee transaction tables |
| `front_cms_*` | Public website CMS tables |
| `online_admission_*` | Public admission form tables |
| `*_id` suffix | Foreign key column |
| `is_active` | Soft-enable flag, values `yes` / `no` or `1` / `0` |
| `created_at` / `updated_at` | DATETIME timestamps, set by app logic |
