# Schema: Homework & Content

16 tables covering homework, file uploads, shared content, video tutorials, and the in-app chat system.

---

## Homework

### `homework`

| Column | Notes |
|---|---|
| `id` | PK |
| `class_section_id` | → `class_sections.id` |
| `subject_id` | → `subjects.id` |
| `staff_id` | → staff (who assigned it) |
| `homework_date` | DATE — date assigned |
| `submission_date` | DATE — deadline |
| `description` | TEXT |
| `attachment`* | Path under `uploads/` |
| `session_id` | |

### `homework_evaluation`

Teacher's evaluation/grading of a student's submitted homework.

| Column | Notes |
|---|---|
| `id` | PK |
| `homework_id` | → `homework.id` |
| `student_session_id` | → `student_session.id` |
| `status` | `submitted` `not_submitted` `graded` |
| `comment`* | Teacher feedback |
| `points`* | Score |
| `evaluation_date`* | DATE |

### `daily_assignment`

Quick daily task (lighter than formal homework — no submission tracking).

### `submit_assignment`

Student homework submission records.

| Column | Notes |
|---|---|
| `id` | PK |
| `homework_id` | → `homework.id` |
| `student_session_id` | → `student_session.id` |
| `attachment`* | Submitted file path |
| `submitted_date` | DATE |

---

## Content / uploads

### `upload_contents`

Files and media uploaded by teachers/admin for sharing with students.

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | |
| `description`* | |
| `type_id` | → `content_types.id` |
| `class_section_id`* | NULL = visible to all |
| `subject_id`* | |
| `staff_id` | Uploader |
| `file` | Path |
| `session_id` | |

### `content_types`

Lookup: Study Material, Notes, Question Paper, Syllabus, etc.

### `contents`

CMS-style rich content entries (distinct from file uploads).

### `share_contents` / `share_upload_contents`

Controls visibility — which class sections can see a piece of content.

---

## Video tutorials

### `video_tutorial`

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | |
| `url` | YouTube / Vimeo URL |
| `staff_id` | |
| `thumbnail`* | |
| `subject_id`* | |
| `session_id` | |

### `video_tutorial_class_sections`

Junction: which class sections can see this video.

---

## Chat

### `chat` / `chat_connections` / `chat_messages` / `chat_users`

Internal school messaging system.

`chat_connections` — pairs of users who have an active conversation.
`chat_messages` — individual messages within a connection.
`chat_users` — online presence tracking.

---

## `messages`

Broadcast messages from admin to students/parents (distinct from peer-to-peer chat).
