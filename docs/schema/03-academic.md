# Schema: Academic / Classes

25 tables covering the academic structure — classes, sections, subjects, timetables, attendance, and the school calendar.

---

## Structure hierarchy

```
classes  (Grade 1, Class 10, etc.)
  └── class_sections  (Class 10 - Section A)
        ├── class_teacher        (who is the class teacher)
        ├── class_batches        (batch/group within a section, for exam purposes)
        │     └── class_batch_subjects  (subjects taught in this batch)
        ├── timetables           (period-wise schedule)
        ├── student_session      (students enrolled in this class+section)
        └── subject_group_class_sections  (which subject groups apply here)

subjects  (Maths, English, Science, etc.)
  ├── subject_groups            (group multiple subjects together for exams)
  │     └── subject_group_subjects
  ├── subject_timetable         (subject → class+section mapping for timetable)
  └── subject_syllabus          (chapter/topic planning)
```

---

## `classes`

| Column | Notes |
|---|---|
| `id` | PK |
| `class` | Display name: `Class 1`, `Grade 10`, `LKG` |

Simple lookup — no session scoping. Classes are defined once and reused across years.

---

## `sections`

| Column | Notes |
|---|---|
| `id` | PK |
| `section` | `A`, `B`, `C`, or custom label |

---

## `class_sections`

Binds a class to a section for the school. This is the unit that gets a timetable, a class teacher, and students.

| Column | Notes |
|---|---|
| `id` | PK |
| `class_id` | → `classes.id` |
| `section_id` | → `sections.id` |

---

## `class_teacher`

Assigns a staff member as the class teacher for a class+section in a session.

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `staff_id` | → `staff.id` (via users) |
| `session_id` | → `sessions.id` |

---

## `class_batches`

A batch is a sub-group within a class section, primarily used in the exam module to define which subjects a group of students is examined on.

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `batch_name` | e.g. `Science Group`, `Commerce Group` |
| `session_id` | → `sessions.id` |

---

## `class_batch_subjects`

Maps subjects to a batch.

| Column | Notes |
|---|---|
| `id` | PK |
| `batch_id` | → `class_batches.id` |
| `subject_id` | → `subjects.id` |

---

## `subjects`

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Subject name |
| `code`* | Short code (e.g. `MATH`, `ENG`) |
| `type`* | `theory` `practical` `both` |

---

## `subject_groups`

Groups subjects for exam result display (e.g. "Main Subjects", "Optional Subjects").

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Group name |
| `session_id` | → `sessions.id` |

## `subject_group_subjects`

| Column | Notes |
|---|---|
| `id` | PK |
| `subject_group_id` | → `subject_groups.id` |
| `subject_id` | → `subjects.id` |

## `subject_group_class_sections`

Assigns a subject group to a specific class section.

| Column | Notes |
|---|---|
| `id` | PK |
| `subject_group_id` | → `subject_groups.id` |
| `class_section_id` | → `class_sections.id` |

---

## `timetables`

Period-wise schedule for a class section.

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `subject_id` | → `subjects.id` |
| `staff_id` | → staff (teacher) |
| `day` | `Monday` … `Saturday` |
| `time_from` | TIME |
| `time_to` | TIME |
| `session_id` | → `sessions.id` |

## `subject_timetable`

Subject-teacher assignment per class section (distinct from the period timetable above — this is the master mapping of "who teaches what to whom").

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `subject_id` | → `subjects.id` |
| `staff_id` | → teaching staff |
| `session_id` | → `sessions.id` |

---

## `subject_syllabus`

Lesson/topic plan for a subject in a class section.

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `subject_id` | → `subjects.id` |
| `lesson_id` | → `lesson.id` |
| `topic_id` | → `topic.id` |
| `session_id` | → `sessions.id` |
| `status` | `complete` / `incomplete` |

## `lesson` / `topic`

Two-level syllabus hierarchy: a lesson contains many topics.

---

## `lesson_plan_forum`

Discussion/notes attached to a lesson plan entry. Teachers post updates here.

---

## Attendance tables

### `attendence_type`

Lookup for attendance status values.

| Column | Notes |
|---|---|
| `id` | PK |
| `type` | `Present` `Absent` `Late` `Half Day` `Holiday` |
| `short_name` | `P` `A` `L` `H` |

### `student_attendences`

Daily class-level attendance. One row per student per day.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `attendence_type_id` | → `attendence_type.id` |
| `date` | DATE |
| `remark`* | TEXT |

### `student_attendence_schedules`

Defines the attendance schedule (which days attendance is taken) for a class section.

### `student_subject_attendances`

Subject-level attendance (distinct from class attendance). One row per student per subject per day.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `subject_id` | → `subjects.id` |
| `date` | DATE |
| `attendence_type_id` | → `attendence_type.id` |

### `student_subject_groups`

Which subject group a student belongs to (for optional subject selection).

---

## Calendar tables

### `annual_calendar`

School year events visible on the calendar view.

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | Event title |
| `date` | DATE |
| `type` | `holiday` `event` `exam` |
| `session_id` | → `sessions.id` |

### `events`

More detailed event entries (can have a time range, target audience, attachment).

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | |
| `start_date` | DATETIME |
| `end_date` | DATETIME |
| `event_for` | `student` `staff` `all` |
| `description`* | |
| `attachment`* | |
| `session_id` | → `sessions.id` |

### `holiday_type`

Types of holidays (National, School, Local).
