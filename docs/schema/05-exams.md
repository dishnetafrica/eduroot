# Schema: Exams & Results

23 tables. The exam module has a deeply nested structure. Understand it top-down before writing any queries.

---

## Structure hierarchy

```
exam_groups          "Half Yearly 2024", "Annual 2024"
  └── exam_group_class_batch_exams      (exam group applied to a class+batch)
        ├── exam_group_class_batch_exam_subjects   (which subjects are examined)
        ├── exam_group_class_batch_exam_students   (which students sit the exam)
        └── exam_group_exam_results                (marks entered per student per subject)

exam_group_exam_connections    (links exam groups for cumulative results)
exam_group_students            (student-level exam group membership)
```

The key insight: **results are stored against exam_group_class_batch_exam_students × exam_group_class_batch_exam_subjects** — not directly against student and subject.

---

## `exam_groups`

An exam event visible in the admin panel (e.g. "Unit Test 1", "Half Yearly").

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | `Unit Test 1` |
| `exam_type` | `basic_system` `school_grade_system` `coll_grade_system` `gpa` `average_passing` |
| `is_active` | `1` / `0` — only active groups appear on student portal |
| `session_id` | → `sessions.id` |
| `description`* | |

### `exam_group_exam_connections`

Links two exam groups so results are combined for a cumulative/final report.

| Column | Notes |
|---|---|
| `id` | PK |
| `exam_group_id` | → `exam_groups.id` (the cumulative group) |
| `child_exam_group_id` | → `exam_groups.id` (the component group) |

---

## `exam_group_class_batch_exams`

Assigns an exam group to a specific class batch (the unit that gets examined).

| Column | Notes |
|---|---|
| `id` | PK — aliased as `egcbe_id` in join queries |
| `exam_group_id` | → `exam_groups.id` |
| `class_section_id` | → `class_sections.id` |
| `batch_id` | → `class_batches.id` |
| `start_date`* | DATE |
| `end_date`* | DATE |

---

## `exam_group_class_batch_exam_subjects`

Which subjects are part of this exam for this class batch.

| Column | Notes |
|---|---|
| `id` | PK |
| `egcbe_id` | → `exam_group_class_batch_exams.id` |
| `subject_id` | → `subjects.id` |
| `total_marks` | Maximum marks |
| `passing_marks` | Minimum to pass |
| `credit_hours`* | For GPA/credit systems |
| `exam_date`* | DATE — specific exam date for this subject |
| `start_time`* | TIME |
| `room_no`* | Exam hall |

---

## `exam_group_class_batch_exam_students`

Which students sit this exam.

| Column | Notes |
|---|---|
| `id` | PK |
| `egcbe_id` | → `exam_group_class_batch_exams.id` |
| `student_session_id` | → `student_session.id` |

---

## `exam_group_exam_results`

The actual marks. One row per student per subject per exam.

| Column | Notes |
|---|---|
| `id` | PK |
| `exam_group_class_batch_exam_student_id` | → `exam_group_class_batch_exam_students.id` |
| `exam_group_class_batch_exam_subject_id` | → `exam_group_class_batch_exam_subjects.id` |
| `get_marks` | DECIMAL — marks obtained (NULL = not entered yet) |
| `attendence` | `present` / `absent` / `medical` |
| `note`* | Teacher remark on this subject result |

---

## `exam_group_students`

Higher-level membership table linking a student to an exam group (used for group-level operations like bulk admit card generation).

---

## `exam_results`

A denormalised summary table written after mark entry — stores calculated totals, percentage, grade, rank per student per exam group. Used for quick report generation without re-joining the full nested structure.

| Column | Notes |
|---|---|
| `id` | PK |
| `exam_group_id` | → `exam_groups.id` |
| `student_session_id` | → `student_session.id` |
| `total_marks` | Sum of all subject marks |
| `percentage` | Calculated |
| `grade`* | Calculated grade |
| `rank`* | Class rank |
| `result` | `pass` / `fail` |
| `published` | `1` / `0` — visible to student portal |

---

## Supporting tables

### `exams`

Scheduled exam entries (for the school calendar view).

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Exam name |
| `exam_group_id` | → `exam_groups.id` |
| `session_id` | → `sessions.id` |

### `exam_schedules`

Period-wise exam timetable (date, time, subject for each class).

### `grades`

Grade boundary definitions.

| Column | Notes |
|---|---|
| `id` | PK |
| `exam_group_id` | → `exam_groups.id` (or NULL for global) |
| `percent_from` | Lower bound |
| `percent_upto` | Upper bound |
| `grade` | `A+` `A` `B` etc. |
| `grade_point`* | For GPA systems |
| `description`* | `Outstanding` `Excellent` etc. |

### `mark_divisions`

Division/distinction cutoffs (First Division ≥ 60%, Pass ≥ 33% etc.).

---

## Template tables

### `template_admitcards`

HTML/CSS template for the admit card PDF. Supports placeholders like `{student_name}`, `{roll_no}`, `{exam_schedule}`.

### `template_marksheets`

HTML/CSS template for the marksheet/report card PDF.

### `certificates`

Custom certificate templates (general purpose — merit, participation, etc.).

---

## Online exam tables

### `onlineexam`

An online exam definition.

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | Exam name |
| `class_section_id` | → `class_sections.id` (or NULL for all) |
| `subject_id`* | → `subjects.id` |
| `start_datetime` | DATETIME |
| `end_datetime` | DATETIME |
| `duration` | Minutes |
| `total_marks` | |
| `pass_marks` | |
| `is_published` | `1` / `0` |
| `session_id` | |

### `questions`

Question bank.

| Column | Notes |
|---|---|
| `id` | PK |
| `question` | TEXT |
| `type` | `single` `multiple` `descriptive` |
| `subject_id` | → `subjects.id` |
| `marks` | Per-question marks |
| `image`* | Path for image-based questions |

### `question_options`

MCQ options.

| Column | Notes |
|---|---|
| `id` | PK |
| `question_id` | → `questions.id` |
| `option_text` | TEXT |
| `is_correct` | `1` / `0` |

### `question_answers`

Student answers (online exam submissions).

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `onlineexam_id` | → `onlineexam.id` |
| `question_id` | → `questions.id` |
| `answer`* | Selected option or descriptive text |

### `onlineexam_questions`

Junction: which questions are in which online exam.

### `onlineexam_students`

Which students are assigned to an online exam.

### `onlineexam_attempts`

Attempt tracking — start time, submit time, IP.

### `onlineexam_student_results`

Calculated result per student after online exam submission.
