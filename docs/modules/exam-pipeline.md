# Module: Exam Pipeline

Step-by-step flow from creating an exam to publishing results to students.

---

## Overview

```
Admin creates exam group (Unit Test 1, Half Yearly, etc.)
  ↓
Assign exam group to class batches
  ↓
Define subjects and marks structure
  ↓
Enrol students into the exam
  ↓
Generate admit cards (optional)
  ↓
Enter marks (teacher or admin)
  ↓
Calculate results (grades, rank, pass/fail)
  ↓
Publish results → visible on student portal
  ↓
Generate marksheets / email PDFs
```

---

## Step 1 — Create exam group

`Admin → Examinations → Exam Groups → Add`

Writes to `exam_groups`.

Key decision at this point: **exam type** (from `app-config.php`):

| Type | When to use |
|---|---|
| `basic_system` | Simple marks + pass/fail |
| `school_grade_system` | Grade-based (A+/A/B/C/D/F) |
| `coll_grade_system` | College-style grading |
| `gpa` | GPA/credit-hour based |
| `average_passing` | Average of all subjects must meet pass mark |

This choice drives which calculation logic runs at result time. **Cannot be changed after marks are entered.**

---

## Step 2 — Assign to class batches

`Admin → Examinations → Exam Groups → [Group] → Add Class`

Writes to `exam_group_class_batch_exams`:
```sql
INSERT INTO exam_group_class_batch_exams 
  (exam_group_id, class_section_id, batch_id, start_date, end_date)
VALUES (1, 5, 3, '2024-09-10', '2024-09-20');
```

One row per class+batch combination. A class 10 with Science and Commerce batches = 2 rows.

---

## Step 3 — Define subjects and marks

For each class batch entry, add the subjects being examined.

Writes to `exam_group_class_batch_exam_subjects`:
```sql
INSERT INTO exam_group_class_batch_exam_subjects
  (egcbe_id, subject_id, total_marks, passing_marks, exam_date)
VALUES (1, 4, 100, 33, '2024-09-12');
```

---

## Step 4 — Enrol students

Students in the class batch are bulk-added to the exam.

Writes to `exam_group_class_batch_exam_students`:
```sql
INSERT INTO exam_group_class_batch_exam_students (egcbe_id, student_session_id)
SELECT 1, ss.id
FROM student_session ss
WHERE ss.class_id = 5 AND ss.section_id = 2 AND ss.session_id = {current_session};
```

---

## Step 5 — Enter marks

`Admin / Teacher → Examinations → Mark Entry`

Writes to `exam_group_exam_results`:
```sql
INSERT INTO exam_group_exam_results
  (exam_group_class_batch_exam_student_id, 
   exam_group_class_batch_exam_subject_id, 
   get_marks, attendence, note)
VALUES (12, 7, 78.5, 'present', NULL);
```

`get_marks = NULL` means marks not yet entered (student has no result yet — distinct from `0` which means zero marks obtained).

---

## Step 6 — Calculate results

After all marks are entered, admin triggers result calculation.

The system:
1. Reads all `exam_group_exam_results` for the exam group
2. Applies grading rules from `grades` table
3. Calculates total, percentage, grade, rank
4. Writes summary to `exam_results`

For cumulative exams (connected via `exam_group_exam_connections`), marks from linked exam groups are summed before calculating.

---

## Step 7 — Publish

`Admin → Examinations → [Exam Group] → Publish`

Updates `exam_results.published = 1` for all students.

After publish:
- Results visible on student portal under "Exam Results"
- `exam_result` notification event fires → email/SMS/WhatsApp sent
- Marksheet PDF becomes downloadable

---

## Step 8 — Marksheet generation

`Admin → Examinations → Marksheet → Print / Email`

Uses `template_marksheets` HTML template with placeholders:
- `{student_name}` `{roll_no}` `{class}` `{section}`
- `{subject_1_name}` `{subject_1_marks}` `{subject_1_grade}` …
- `{total_marks}` `{percentage}` `{grade}` `{rank}` `{result}`

The `email_pdf_exam_marksheet` notification event emails the PDF to the student's registered email.

---

## Online exams — separate flow

Online exams (`onlineexam` table) are a parallel system, not connected to the offline exam pipeline above. They have their own question bank, timer, and result tables. Results from online exams do not automatically feed into `exam_group_exam_results`.

---

## Common mistakes

| Mistake | Consequence |
|---|---|
| Changing exam type after mark entry | Calculation results change, old results invalid |
| Entering `0` instead of `NULL` for ungraded | Student appears to have scored zero |
| Not running calculate before publish | Students see blank results |
| Editing marks after publish | Need to unpublish → recalculate → republish |
| Missing student in `exam_group_class_batch_exam_students` | Student doesn't appear on mark entry screen |
