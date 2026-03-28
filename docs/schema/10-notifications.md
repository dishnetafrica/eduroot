# Schema: Notifications

10 tables covering email, SMS, push, and WhatsApp notification configuration and delivery tracking.

---

## Configuration tables

### `email_config`

SMTP settings. One active row per school.

| Column | Notes |
|---|---|
| `id` | PK |
| `mail` | From address |
| `name` | From name |
| `host` | SMTP host |
| `port` | INT |
| `encryption` | `ssl` `tls` `` (none) |
| `username` | SMTP auth user |
| `password` | SMTP auth password — encrypted at rest |

### `sms_config`

SMS gateway credentials. One active row per school.

| Column | Notes |
|---|---|
| `id` | PK |
| `gateway` | Gateway name string |
| `api_key`* | |
| `api_secret`* | |
| `sender_id`* | |
| `extra`* | JSON — gateway-specific extra params |

---

## Template tables

### `email_template`

HTML email templates per notification event.

| Column | Notes |
|---|---|
| `id` | PK |
| `mail_type` | Event key — matches keys in `application/config/mailsms.php` |
| `subject` | Email subject line (supports `{placeholders}`) |
| `body` | HTML body (supports `{placeholders}`) |
| `is_active` | `1` / `0` — school can disable any event |

### `email_template_attachment`

Files attached to specific email template events (e.g. fee receipt attached to `fee_submission` email).

| Column | Notes |
|---|---|
| `id` | PK |
| `email_template_id` | → `email_template.id` |
| `file` | Path or type indicator |

### `sms_template`

SMS message templates, one per event key.

| Column | Notes |
|---|---|
| `id` | PK |
| `sms_type` | Event key |
| `body` | SMS text (supports `{placeholders}`) |
| `is_active` | |

---

## Delivery tracking tables

### `send_notification`

Log of every notification sent.

| Column | Notes |
|---|---|
| `id` | PK |
| `title` | Notification title |
| `message` | Body |
| `type` | `email` `sms` `push` `whatsapp` |
| `send_to` | `student` `staff` `all` |
| `is_read`* | |
| `created_at` | DATETIME |

### `read_notification`

Tracks which users have read a push notification.

| Column | Notes |
|---|---|
| `id` | PK |
| `notification_id` | → `send_notification.id` |
| `user_id` | → `users.id` |
| `read_at` | DATETIME |

### `email_attachments`

Outbound email attachment files queued for sending (used for bulk sends with attachments like payslips, fee receipts).

---

## Settings tables

### `notification_setting`

Per-event toggle — which channels are enabled for each notification event.

| Column | Notes |
|---|---|
| `id` | PK |
| `notification_type` | Event key |
| `sms` | `1` / `0` |
| `email` | `1` / `0` |
| `push` | `1` / `0` |
| `whatsapp` | `1` / `0` |

### `notification_roles`

Which roles receive each notification type.

| Column | Notes |
|---|---|
| `id` | PK |
| `notification_type` | Event key |
| `student` | `1` / `0` |
| `parent` | `1` / `0` |
| `staff` | `1` / `0` |

---

## All notification event keys

Defined in `application/config/mailsms.php`. These keys are used across `email_template.mail_type`, `sms_template.sms_type`, `notification_setting.notification_type`, and in the WhatsApp settings:

| Key | Trigger |
|---|---|
| `student_admission` | New student admitted |
| `fee_submission` | Fee payment recorded |
| `group_fee_submission` | Bulk fee collection |
| `fee_processing` | Online fee initiated |
| `fees_reminder` | Fee reminder cron |
| `student_absent_attendence` | Student marked absent |
| `student_present_attendence` | Student marked present |
| `staff_absent_attendence` | Staff marked absent |
| `staff_present_attendence` | Staff marked present |
| `exam_result` | Exam results published |
| `email_pdf_exam_marksheet` | Marksheet emailed |
| `homework` | Homework assigned |
| `homework_evaluation` | Homework graded |
| `online_classes` | Online class created |
| `online_meeting` | Meeting scheduled |
| `online_examination_publish_exam` | Online exam published |
| `online_examination_publish_result` | Online exam result published |
| `login_credential` | Staff login credentials sent |
| `student_login_credential` | Student login credentials sent |
| `staff_login_credential` | Same as above (alias) |
| `forgot_password` | Password reset request |
| `alumni_student` | Student promoted to alumni |
| `online_admission_form_submission` | Online admission form submitted |
| `online_admission_fees_submission` | Admission fee paid |
| `online_admission_fees_processing` | Admission fee initiated |
| `student_apply_leave` | Student leave application |
