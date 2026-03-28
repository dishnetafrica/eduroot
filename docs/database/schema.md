# Database Schema

EduRoot uses a single MySQL database with ~100 tables. Below is the complete table inventory grouped by domain, with key columns and relationships documented.

---

## Table Groups

### 1. Core / School Setup

| Table | Description | Key Columns |
|---|---|---|
| `sch_settings` | School profile and all system settings | `id`, `name`, `address`, `phone`, `email`, `logo`, `saas_*`, `whatsapp_*`, `currency_id` |
| `sessions` | Academic years / terms | `id`, `session`, `is_active` |
| `classes` | Class/grade definitions | `id`, `class`, `sections` |
| `sections` | Section definitions | `id`, `section_name` |
| `class_sections` | Class ↔ section mapping per session | `id`, `class_id`, `section_id`, `session_id` |
| `class_batches` | Batch/group within a class-section | `id`, `class_id`, `section_id`, `batch_name` |
| `class_batch_subjects` | Subjects assigned to a batch | `id`, `batch_id`, `subject_id`, `teacher_id` |
| `subjects` | Subject master | `id`, `name`, `type`, `code` |
| `subject_groups` | Subject groupings for batch assignment | `id`, `name`, `class_id`, `section_id` |
| `categories` | General-purpose categorisation | `id`, `category`, `type` |
| `roles` | User role definitions | `id`, `role_name` |
| `roles_permissions` | Role → module permission mapping | `id`, `role_id`, `permission_id`, `module` |
| `sidebar_menus` | Sidebar nav item master | `id`, `name`, `url`, `icon`, `role`, `sort_order` |
| `sidebar_sub_menus` | Sidebar sub-menu items | `id`, `menu_id`, `name`, `url`, `role` |
| `modules` | Module registry (for addon system) | `id`, `module_name`, `status` |
| `currencies` | Currency master with exchange rates | `id`, `currency_name`, `symbol`, `exchange_rate`, `is_active` |
| `languages` | Installed language packs | `id`, `language`, `lang_code`, `is_rtl`, `is_active` |
| `filetypes` | Allowed upload MIME types per context | `id`, `type`, `extension`, `mime` |

---

### 2. Users & Auth

| Table | Description | Key Columns |
|---|---|---|
| `users` | Unified login table for all roles | `id`, `username`, `password` (bcrypt), `role`, `email`, `mobile_no`, `school_id`, `is_active` |
| `admin` | Admin profile | `id`, `user_id`, `name`, `phone`, `photo` |
| `accountants` | Accountant profile | `id`, `user_id`, `name`, `phone`, `photo` |
| `userlog` | Login/logout audit log | `id`, `user_id`, `ip_address`, `browser`, `login_at`, `logout_at` |

---

### 3. Students

| Table | Description | Key Columns |
|---|---|---|
| `students` | Student master record | `id`, `admission_no`, `firstname`, `lastname`, `dob`, `gender`, `blood_group`, `religion`, `caste`, `father_name`, `mother_name`, `guardian_id`, `photo`, `mobileno`, `email`, `current_address`, `permanent_address`, `is_active`, `disable_reason` |
| `student_session` | Student ↔ academic year enrollment | `id`, `student_id`, `session_id`, `class_id`, `section_id`, `roll_no`, `is_active` |
| `student_doc` | Uploaded student documents | `id`, `student_id`, `doc_title`, `doc_file` |
| `student_sibling` | Sibling linkage between students | `id`, `student_id`, `sibling_id` |
| `student_subject_groups` | Student ↔ subject group assignment | `id`, `student_session_id`, `subject_group_id` |
| `student_educational_details` | Previous school records | `id`, `student_session_id`, `school_name`, `board`, `class`, `year` |
| `student_skills_detail` | Skills / extra-curricular records | `id`, `student_session_id`, `skill`, `detail` |
| `student_work_experience` | Work experience records | `id`, `student_session_id`, `org_name`, `designation` |
| `student_applyleave` | Student leave applications | `id`, `student_id`, `session_id`, `from_date`, `to_date`, `reason`, `status` |
| `student_edit_fields` | Configurable student profile fields | `id`, `field_name`, `is_required`, `is_active` |
| `student_dashboard_settings` | Per-student dashboard widget config | `id`, `student_id`, `widget`, `is_visible` |
| `student_refrence` | Referral/source tracking | `id`, `student_id`, `reference_id` |
| `disable_reason` | Reasons for disabling a student | `id`, `reason`, `type` |
| `reference` | Lead source / reference master | `id`, `reference` |
| `source` | Admission source master | `id`, `source` |
| `alumni_students` | Students moved to alumni status | `id`, `student_id`, `session_id`, `pass_year` |
| `alumni_events` | Alumni events | `id`, `title`, `description`, `date`, `photo` |

---

### 4. Staff & HRM

| Table | Description | Key Columns |
|---|---|---|
| `staff` | Staff master record | `id`, `user_id`, `name`, `employee_id`, `department_id`, `designation_id`, `qualification`, `experience`, `phone`, `email`, `dob`, `gender`, `marital_status`, `date_of_joining`, `photo`, `is_active` |
| `teachers` | Teacher-specific data | `id`, `staff_id`, `user_id` |
| `department` | Department master | `id`, `name` |
| `staff_designation` | Designation/job title master | `id`, `designation_name`, `department_id` |
| `staff_documents` | Staff document uploads | `id`, `staff_id`, `doc_title`, `doc_file` |
| `staff_attendance` | Daily staff attendance records | `id`, `staff_id`, `date`, `type` (1=present, 2=late, 3=absent, 4=halfday, 5=holiday) |
| `staff_leave_request` | Staff leave applications | `id`, `staff_id`, `leave_type_id`, `from_date`, `to_date`, `reason`, `status`, `approved_by` |
| `staff_leave_details` | Approved leave allocation per type | `id`, `staff_id`, `leave_type_id`, `no_of_days` |
| `staff_payslip` | Generated payslips | `id`, `staff_id`, `month`, `year`, `basic`, `allowances`, `deductions`, `net_pay`, `status` |
| `payslip_allowance` | Per-payslip allowance/deduction line items | `id`, `payslip_id`, `head`, `amount`, `type` |
| `staff_timeline` | Staff activity feed entries | `id`, `staff_id`, `title`, `description`, `date` |
| `staff_id_card` | ID card template per staff | `id`, `staff_id`, `template_id` |
| `staff_rating` | Performance ratings | `id`, `staff_id`, `rating`, `remarks`, `session_id` |
| `staff_roles` | Staff ↔ role mapping | `id`, `staff_id`, `role_id` |
| `leavetypes` | Leave type master | `id`, `leave_type`, `type` |
| `staffAttendaceSetting` | Staff attendance configuration | `id`, `school_id`, `late_mark_time`, `half_day_time` |
| `resume_settings_fields` | Resume builder field config | `id`, `field_name`, `is_active`, `sort_order` |
| `resume_additional_fields_settings` | Extra resume fields | `id`, `field_name`, `is_active` |

---

### 5. Fees & Finance

| Table | Description | Key Columns |
|---|---|---|
| `feetype` | Fee type master (tuition, transport, hostel…) | `id`, `feetype_name`, `type` |
| `feecategory` | Fee category grouping | `id`, `name` |
| `fee_groups` | Named fee group (e.g. "Term 1 Fees") | `id`, `name`, `description`, `session_id`, `class_id` |
| `fee_groups_feetype` | Fee types included in a fee group | `id`, `fee_group_id`, `feetype_id`, `amount`, `due_date` |
| `fee_session_groups` | Student ↔ fee group enrollment | `id`, `student_session_id`, `fee_group_id` |
| `feemasters` | Fee master (fine-grained fee record per student) | `id`, `student_session_id`, `fee_group_id`, `feetype_id`, `amount`, `discount`, `due_date` |
| `student_fees_master` | Consolidated fee record per student-session | `id`, `student_session_id`, `session_id` |
| `student_fees` | Individual fee line items | `id`, `student_fees_master_id`, `feetype_id`, `amount`, `due_date` |
| `student_fees_deposite` | Fee payment receipts | `id`, `student_fees_master_id`, `receipt_no`, `amount`, `payment_mode`, `date`, `collected_by`, `school_id` |
| `student_fees_processing` | Online payment gateway processing records | `id`, `student_fees_master_id`, `gateway`, `txn_id`, `amount`, `status` |
| `student_fees_discounts` | Applied discounts per student | `id`, `student_fees_master_id`, `feetype_id`, `discount_type`, `discount`, `discount_amount` |
| `fees_discounts` | Discount master | `id`, `name`, `type` (percentage/fixed), `amount` |
| `fee_receipt_no` | Auto-incrementing receipt number per school | `id`, `school_id`, `receipt_no` |
| `fees_reminder` | Fee reminder schedule settings | `id`, `school_id`, `before_days`, `message_type` |
| `expenses` | Expense records | `id`, `expense_head_id`, `amount`, `date`, `note`, `doc_file`, `session_id` |
| `expense_head` | Expense category master | `id`, `head_name`, `description` |
| `incomes` | Non-fee income records | `id`, `income_head_id`, `amount`, `date`, `note` |
| `income_head` | Income category master | `id`, `head_name` |
| `payment_settings` | Payment gateway config per school | `id`, `school_id`, `gateway`, `api_key`, `secret_key`, `is_active` |
| `gateway_ins` | Instalment payment records | `id`, `student_fees_master_id`, `amount`, `due_date`, `paid_date`, `status` |
| `gateway_ins_response` | Gateway callback logs | `id`, `gateway_ins_id`, `response_data`, `status` |
| `cumulative_fine` | Accumulated late payment fines | `id`, `student_fees_master_id`, `fine_amount`, `date` |

---

### 6. Attendance

| Table | Description | Key Columns |
|---|---|---|
| `student_attendences` | Daily class-wise attendance | `id`, `student_id`, `student_session_id`, `date`, `attendance_type_id`, `class_section_id` |
| `student_subject_attendances` | Subject-level attendance | `id`, `student_id`, `subject_id`, `date`, `class_section_id`, `type` |
| `attedance_type` (sic) | Attendance type master | `id`, `type`, `short_name` |
| `studentAttendanceSetting` | Attendance configuration per school | `id`, `school_id`, `working_days`, `late_mark_notify` |

---

### 7. Exams & Results

| Table | Description | Key Columns |
|---|---|---|
| `exams` | Exam master | `id`, `exam`, `note`, `session_id` |
| `exam_schedules` | Exam timetable entries | `id`, `exam_id`, `class_id`, `section_id`, `subject_id`, `date`, `start_time`, `end_time` |
| `exam_groups` | Exam group (links multiple exams) | `id`, `exam_group_name`, `exam_type`, `class_id`, `section_id`, `session_id` |
| `exam_group_class_batch_exams` | Exams within a group for a class-batch | `id`, `exam_group_id`, `class_section_id`, `batch_id`, `exam_id` |
| `exam_group_class_batch_exam_subjects` | Subjects in each group exam | `id`, `exam_group_class_batch_exam_id`, `subject_id`, `max_marks`, `passing_marks` |
| `exam_group_students` | Students enrolled in an exam group | `id`, `exam_group_id`, `student_session_id` |
| `exam_group_exam_results` | Final result per student per exam group | `id`, `exam_group_id`, `student_session_id`, `total_marks`, `percentage`, `grade`, `rank`, `pass_fail`, `is_published` |
| `exam_results` | Individual subject marks | `id`, `exam_group_class_batch_exam_id`, `student_session_id`, `subject_id`, `marks_obtained`, `grade`, `remark` |
| `exam_group_exam_connections` | Links/connections between exam groups | `id`, `parent_exam_group_id`, `child_exam_group_id` |
| `grades` | Grade master (A+, A, B…) | `id`, `exam_type`, `grade_name`, `percent_from`, `percent_to`, `grade_point` |
| `marks_divisions` | Pass/fail division configuration | `id`, `exam_type`, `name`, `percentage_from`, `percentage_to` |
| `marksheet_templates` | Marksheet PDF template config | `id`, `template_name`, `settings_json` |
| `template_admitcards` | Admit card PDF template config | `id`, `template_name`, `settings_json` |
| `template_marksheets` | Marksheet template settings | `id`, `name`, `html_content` |

---

### 8. Online Exams

| Table | Description | Key Columns |
|---|---|---|
| `onlineexam` | Online exam definition | `id`, `exam_name`, `class_id`, `section_id`, `subject_id`, `duration`, `start_date`, `end_date`, `total_marks`, `pass_marks`, `is_published` |
| `onlineexam_questions` | Questions in an online exam | `id`, `exam_id`, `question_id` |
| `onlineexam_attempts` | Student attempt records | `id`, `exam_id`, `student_session_id`, `start_time`, `end_time`, `marks_obtained`, `status` |
| `questions` | Question bank | `id`, `question`, `subject_id`, `class_id`, `type`, `marks` |
| `question_options` | Answer options per question | `id`, `question_id`, `option_text`, `is_correct` |
| `question_answers` | Student submitted answers | `id`, `attempt_id`, `question_id`, `selected_option_id` |
| `submit_assignment` | Homework/assignment submissions | `id`, `homework_id`, `student_session_id`, `submit_date`, `file`, `status` |

---

### 9. Homework & Lesson Planning

| Table | Description | Key Columns |
|---|---|---|
| `homework` | Homework assignments | `id`, `class_id`, `section_id`, `subject_id`, `homework_date`, `submission_date`, `description`, `attach_file` |
| `homework_evaluation` | Teacher evaluation of submissions | `id`, `homework_id`, `student_session_id`, `marks_obtained`, `remarks` |
| `daily_assignment` | Daily assignment records | `id`, `student_session_id`, `homework_id`, `submit_date`, `file`, `evaluated` |
| `lesson_plan` | Lesson plans | `id`, `class_id`, `section_id`, `subject_id`, `teacher_id`, `topic`, `start_date`, `end_date` |
| `subject_syllabus` | Syllabus per subject/class | `id`, `class_id`, `section_id`, `subject_id`, `topic`, `date` |
| `topic` | Lesson plan topics | `id`, `lesson_plan_id`, `topic_name`, `complete_date`, `is_completed` |
| `lesson_plan_forum` | Lesson plan discussion | `id`, `lesson_plan_id`, `staff_id`, `comment`, `created_at` |
| `timetables` | Class timetable entries | `id`, `class_section_id`, `subject_id`, `teacher_id`, `day`, `time_from`, `time_to` |

---

### 10. Transport

| Table | Description | Key Columns |
|---|---|---|
| `vehicles` | School vehicle master | `id`, `vehicle_no`, `vehicle_model`, `driver_name`, `driver_licence`, `driver_phone`, `note` |
| `transport_route` | Route master | `id`, `route_title`, `fare` |
| `route_pickup_point` | Pickup points on a route | `id`, `route_id`, `pickup_point_id`, `pickup_time` |
| `pickup_point` | Pickup point master | `id`, `pickup_point_name`, `address` |
| `student_transport_fees` | Student ↔ transport fee assignment | `id`, `student_session_id`, `transport_route_id`, `pickup_point_id`, `feetype_id`, `amount` |

---

### 11. Library

| Table | Description | Key Columns |
|---|---|---|
| `books` | Book catalog | `id`, `title`, `author`, `isbn`, `publisher`, `subject_id`, `rack_no`, `quantity` |
| `book_issues` | Issue/return records | `id`, `book_id`, `user_id`, `issue_date`, `return_date`, `actual_return_date`, `fine`, `status` |
| `library_members` | Library membership | `id`, `user_id`, `role`, `member_id`, `valid_from`, `valid_to` |
| `cumulative_fine` | Accumulated library fines | (shared with fee module) |

---

### 12. Hostel

| Table | Description | Key Columns |
|---|---|---|
| `hostel` | Hostel master | `id`, `hostel_name`, `type`, `address`, `phone` |
| `room_types` | Room type master | `id`, `room_type`, `hostel_id`, `capacity`, `amount` |
| `hostel_rooms` | Individual rooms | `id`, `hostel_id`, `room_type_id`, `room_no`, `capacity`, `amount` |
| `hostel_members` | Student hostel allocations | `id`, `student_session_id`, `room_id`, `check_in`, `check_out` |

---

### 13. Inventory / Stores

| Table | Description | Key Columns |
|---|---|---|
| `item_categories` | Item category master | `id`, `category_name` |
| `items` | Item master | `id`, `item_name`, `category_id`, `supplier_id`, `unit`, `store_id` |
| `item_stock` | Stock quantity per item | `id`, `item_id`, `quantity`, `date` |
| `item_stores` | Store/warehouse master | `id`, `store_name`, `description` |
| `item_suppliers` | Supplier master | `id`, `name`, `phone`, `email`, `address` |
| `item_issues` | Item issue records | `id`, `item_id`, `user_id`, `quantity`, `issue_date`, `return_date`, `status` |

---

### 14. Communication

| Table | Description | Key Columns |
|---|---|---|
| `email_config` | SMTP settings per school | `id`, `school_id`, `smtp_host`, `smtp_username`, `smtp_password`, `smtp_port`, `smtp_encryption` |
| `sms_config` | SMS gateway settings per school | `id`, `school_id`, `gateway`, `api_key`, `sender_id` |
| `email_template` | Customisable email templates | `id`, `school_id`, `event_key`, `subject`, `body_html` |
| `email_attachments` | Attachments linked to email templates | `id`, `template_id`, `file_path` |
| `email_template_attachment` | Per-send attachment records | `id`, `email_log_id`, `file_path` |
| `sms_template` | SMS templates per event | `id`, `school_id`, `event_key`, `template` |
| `send_notification` | Sent notification log | `id`, `user_id`, `title`, `message`, `type`, `sent_at` |
| `read_notification` | Notification read receipts | `id`, `notification_id`, `user_id`, `read_at` |
| `notification_settings` | Per-event notification toggle | `id`, `school_id`, `event_key`, `email`, `sms`, `push`, `whatsapp` |
| `messages` | Internal messaging | `id`, `from_id`, `to_id`, `message`, `created_at` |
| `chat` | Chat room definitions | `id`, `name`, `type` |
| `chat_users` | Chat room members | `id`, `chat_id`, `user_id` |
| `chat_messages` | Chat messages | `id`, `chat_id`, `user_id`, `message`, `created_at` |
| `chat_connections` | Peer-to-peer connection state | `id`, `user_a`, `user_b`, `status` |

---

### 15. Online Admission

| Table | Description | Key Columns |
|---|---|---|
| `online_admissions` | Public admission applications | `id`, `session_id`, `class_id`, `firstname`, `lastname`, `dob`, `gender`, `guardian_name`, `email`, `mobile`, `status` |
| `online_admission_fields` | Configurable admission form fields | `id`, `field_name`, `field_label`, `is_required`, `is_active`, `sort_order` |
| `online_admission_custom_field_value` | Custom field responses | `id`, `admission_id`, `field_id`, `value` |
| `online_admission_payment` | Admission fee payment records | `id`, `admission_id`, `gateway`, `txn_id`, `amount`, `status`, `paid_at` |

---

### 16. CMS / Front Office

| Table | Description | Key Columns |
|---|---|---|
| `front_cms_settings` | Website settings | `id`, `school_id`, `key`, `value` |
| `front_cms_pages` | Static CMS pages | `id`, `title`, `slug`, `is_active` |
| `front_cms_page_contents` | Page content blocks | `id`, `page_id`, `content`, `sort_order` |
| `front_cms_menus` | Navigation menus | `id`, `name`, `location` |
| `front_cms_menu_items` | Menu links | `id`, `menu_id`, `label`, `url`, `parent_id`, `sort_order` |
| `front_cms_programs` | Academic programs listing | `id`, `title`, `description`, `image` |
| `front_cms_program_photos` | Program photo gallery | `id`, `program_id`, `photo` |
| `front_cms_media_gallery` | General media gallery | `id`, `title`, `file_path`, `type` |
| `annual_calendar` | Published annual calendar events | `id`, `school_id`, `title`, `date`, `type` |
| `visitors_book` | Visitor log | `id`, `name`, `phone`, `to_meet`, `purpose_id`, `in_time`, `out_time`, `date` |
| `visitors_purpose` | Visitor purpose master | `id`, `purpose` |
| `general_calls` | Phone call log | `id`, `call_date`, `name`, `phone`, `type`, `duration`, `note` |
| `dispatch` | Outbound mail dispatch log | `id`, `from_title`, `reference_no`, `address`, `note`, `date`, `attach` |

---

### 17. Enquiry & Leads

| Table | Description | Key Columns |
|---|---|---|
| `enquiry` | Admission enquiry leads | `id`, `name`, `phone`, `email`, `class_id`, `source_id`, `status`, `assigned_to` |
| `follow_up` | Enquiry follow-up log | `id`, `enquiry_id`, `date`, `note`, `next_follow_up_date`, `assigned_to` |

---

### 18. Certificates & ID Cards

| Table | Description | Key Columns |
|---|---|---|
| `certificates` | Certificate template master | `id`, `title`, `header`, `body`, `footer`, `left_margin`, `print_header` |
| `transfer_certificate_settings` | TC template configuration | `id`, `school_id`, `header_html`, `footer_html` |
| `transfer_certificate_fields` | TC form field definitions | `id`, `field_name`, `field_label`, `is_active` |
| `transfer_certificate_no` | Auto-incrementing TC number | `id`, `school_id`, `tc_no` |
| `generate_id_card` | Student ID card template | `id`, `school_id`, `template`, `settings_json` |
| `staff_id_card` | Staff ID card template | `id`, `school_id`, `template`, `settings_json` |

---

### 19. Custom Fields

| Table | Description | Key Columns |
|---|---|---|
| `custom_fields` | Custom field definitions (student/staff) | `id`, `field_name`, `field_type`, `for_table`, `is_required`, `sort_order` |
| `custom_field_values` | Custom field values | `id`, `custom_field_id`, `record_id`, `value` |

---

### 20. Audit & Logs

| Table | Description | Key Columns |
|---|---|---|
| `userlog` | Login/logout events | `id`, `user_id`, `ip_address`, `browser`, `login_at`, `logout_at` |
| `share_contents` | Content sharing log | `id`, `content_id`, `shared_by`, `shared_to`, `shared_at` |
| `upload_contents` | File upload tracking | `id`, `user_id`, `file_name`, `file_path`, `type`, `created_at` |
| `content_types` | Uploadable content type master | `id`, `type_name`, `icon` |
| `contents` | Shared content records | `id`, `uploader_id`, `title`, `file_path`, `content_type_id`, `class_id`, `section_id` |

---

## Key Relationships

```
students (1) ──────────────────── (many) student_session
student_session (1) ─────────────(many) student_attendences
student_session (1) ─────────────(many) student_fees_master
student_fees_master (1) ─────────(many) student_fees
student_fees_master (1) ─────────(many) student_fees_deposite
student_session (1) ─────────────(many) exam_group_students
exam_groups (1) ────────────────(many) exam_group_exam_results
staff (1) ───────────────────────(many) staff_attendance
staff (1) ───────────────────────(many) staff_payslip
users (1) ─── students / teachers / staff / admin / accountants
classes (m) ────────────────── (m) sections   [via class_sections]
class_sections (1) ─────────────(many) timetables
class_sections (1) ─────────────(many) student_attendences
```

---

## Naming Conventions

| Convention | Example |
|---|---|
| Plural table names | `students`, `staff`, `classes` |
| Snake case | `student_session`, `fee_groups_feetype` |
| Primary key always `id` | `id` INT AUTO_INCREMENT PRIMARY KEY |
| Foreign keys | `student_id`, `class_id`, `session_id` |
| Soft delete via flag | `is_active`, `is_deleted` (no hard DELETE in most tables) |
| Timestamps | `created_at`, `updated_at` (not all tables have these — inconsistency to fix) |
