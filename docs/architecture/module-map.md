# Module Map

Every feature in EduRoot maps to a controller. This file is the lookup table.

---

## Admin panel (`application/controllers/admin/`)

| Feature | Controller | Key Models |
|---|---|---|
| Dashboard & school settings | `Admin.php` | `Admin_model`, `Sch_settings_model` |
| Student management | `admin/` (see Student controllers) | `Student_model`, `Studentsession_model` |
| Staff management | `Staff.php` | `Staff_model`, `Teacher_model` |
| Classes & sections | `Classes.php` → `Sections.php` | `Class_model`, `Classection_model` |
| Subjects | `Subject.php` | `Subject_model` |
| Timetable | `Timetable.php` | `Timetable_model`, `Subjecttimetable_model` |
| Attendance (student) | `Stuattendence.php` | `Student_model`, `Attendencetype_model` |
| Attendance (subject) | `Subjectattendence.php` | `Studentsubjectattendence_model` |
| Attendance (staff) | `Staffattendance.php` | `Staff_model` |
| Fee types | `Feetype.php` | `Feetype_model` |
| Fee categories | `Feecategory.php` | `Feecategory_model` |
| Fee masters | `admin/Feemaster.php` | `Feemaster_model` |
| Fee groups | `admin/Feegroup.php` | `Feegroup_model` |
| Fee discounts | `admin/Feediscount.php` | `Feediscount_model` |
| Fee reminders | `admin/Feereminder.php` | `Feesreminder_model` |
| Fee forward (year-end) | `admin/Feesforward.php` | `Studentsession_model` |
| Collect fees (student) | `Studentfee.php` (root) | `Studentfeemaster_model`, `Studentfee_model` |
| Exams | `admin/Exam.php` | `Exam_model` |
| Exam groups | `admin/Examgroup.php` | `Examgroup_model` |
| Exam schedules | `admin/Examschedule.php` | `Exam_schedule_model` |
| Exam results (entry) | `admin/Examresult.php` | `Examresult_model` |
| Online exam | `admin/Onlineexam.php` | `Onlineexam_model` |
| Questions (online exam) | `admin/Question.php` | `Question_model` |
| Marks & division | `admin/Mark.php` → `admin/Marksdivision.php` | `Marksdivision_model` |
| Marksheet templates | `admin/Marksheet.php` | `Marksheet_model` |
| Admit card templates | `admin/Admitcard.php` | `Admitcard_model` |
| Homework | `Homework.php` (root) | `Homework_model` |
| Syllabus | `admin/Syllabus.php` | `Syllabus_model` |
| Lesson plan | (inside Syllabus) | `Syllabus_model` |
| Payroll | `admin/Payroll.php` | `Payroll_model` |
| Leave management | `admin/Approve_leave.php` | `Apply_leave_model` |
| Department & designation | `admin/Department.php` → `admin/Designation.php` | `Department_model` |
| Transport (vehicles) | `admin/Vehicle.php` | `Vehicle_model` |
| Transport (routes) | `admin/Route.php` → `admin/Vehroute.php` | `Vehroute_model` |
| Transport (pickup) | `admin/Pickuppoint.php` | — |
| Library (books) | `admin/Book.php` | `Book_model`, `Bookissue_model` |
| Hostel | `admin/Roomtype.php` → `admin/Schoolhouse.php` | — |
| Expenses | `admin/Expense.php` | `Expense_model` |
| Enquiry / CRM | `admin/Enquiry.php` | `Enquiry_model` |
| Online admission | `admin/Onlineadmission.php` | `Onlineadmission_model` |
| Certificates | `admin/Certificate.php` | `Certificate_model` |
| Transfer certificate | `admin/Transfercertificate.php` | `Transfercertificate_model` |
| ID cards (student) | `admin/Studentidcard.php` | `Student_id_card_model` |
| ID cards (staff) | `admin/Staffidcard.php` | `Staff_model` |
| Notifications | `admin/Notification.php` | `Notification_model` |
| Email config | `Emailconfig.php` (root) | `Email_config_model` |
| SMS config | `Smsconfig.php` (root) | `Sms_config_model` |
| WhatsApp settings | (inside `Schsettings.php`) | `Sch_settings_model` |
| Chat | `admin/Chat.php` | `Chat_model` |
| Calendar | `admin/Calendar.php` | `Calendar_model` |
| Timeline | `admin/Timeline.php` | `Timeline_model` |
| Visitors | `admin/Visitors.php` | `Visitors_model` |
| Dispatch | `admin/Dispatch.php` | — |
| Complaints | `admin/Complaint.php` | `Complaint_Model` |
| Roles & permissions | `admin/Roles.php` | `Userpermission_model` |
| Users (admin) | `admin/Users.php` | `User_model` |
| Addons | `admin/Addons.php` | `Addons_model` |
| Front CMS | `admin/Frontcms.php` → `admin/Content.php` | `Cms_page_model` |
| School settings | `Schsettings.php` (root) | `Sch_settings_model` |
| Payment settings | `admin/Paymentsettings.php` | — |
| Currency | `admin/Currency.php` | — |
| Audit log | `admin/Audit.php` | `Audit_model` |
| User log | `admin/Userlog.php` | `Userlog_model` |
| Biometric | `Biometric.php` (root) | — |
| Alumni | `admin/Alumni.php` | `Alumni_model` |
| Student transfer | `admin/Stdtransfer.php` | `Studentsession_model` |
| Balance fees | `Balancefees.php` (root) | `Balancefees_model` |
| Finance reports | `Financereports.php` (root) | `Financereports_model` |
| Attendance reports | `Attendencereports.php` (root) | — |
| General reports | `Report.php` (root) | `Report_model` (120KB!) |
| System updater | `admin/Updater.php` | — |
| Video tutorials | `admin/Video_tutorial.php` | `Video_tutorial_model` |

---

## Student / parent portal (`application/controllers/user/`)

| Feature | Controller |
|---|---|
| Dashboard, profile | `user/Default.php` |
| Fee payment | `user/Studentfee.php` + `user/gateway/` |
| Attendance view | `user/Attendence.php` |
| Homework | `user/Homework.php` |
| Exam results | `user/Exam.php` → `user/Mark.php` |
| Online exam | `user/Onlineexam.php` |
| Timetable | `user/Timetable.php` |
| Syllabus | `user/Syllabus.php` |
| Leave application | `user/Apply_leave.php` |
| Library | `user/Book.php` |
| Transport | `user/Route.php` |
| Hostel | `user/Hostelroom.php` |
| Chat | `user/Chat.php` |
| Calendar | `user/Calendar.php` |
| Timeline | `user/Timeline.php` |
| Notifications | `user/Notification.php` |
| Visitors | `user/Visitors.php` |
| Video tutorials | `user/Video_tutorial.php` |
| Teacher access | `user/Teacher.php` |
| Content / downloads | `user/Content.php` → `user/Subject.php` |

---

## Public / front-end (`application/controllers/`)

| Feature | Controller |
|---|---|
| School website & CMS | `Welcome.php` |
| Online admission form | `onlineadmission/` + payment gateways |
| Exam result lookup | `Welcome.php::examresult()` |
| Password reset | `Site.php` |
| Login (all roles) | `Site.php` |
| Cron jobs | `Cron.php` |
| Webhook callbacks | `Webhooks.php` |
| Token-based downloads | `Site.php` (receipt, marksheet) |

---

## Payment gateways

Both `user/gateway/` and `onlineadmission/` contain the same 28 gateways:

```
Billplz  Cashfree  CCAvenue  Checkout  DpoPay  Flutterwave  Ihela
Instamojo  IpayAfrica  JazzCash  Kowri  Midtrans  Mollie  MomoPay
Onepay  PayFast  PayHere  PayPal  Paystack  Paytm  PayU  Pesapal
Razorpay  Skrill  SSLCommerz  Stripe  Toyyibpay  2Checkout  Walkingm
```

Each gateway controller handles: redirect to gateway, callback/webhook, and update of `student_fees` + `gateway_ins_response`.

---

## Model naming convention

| Pattern | Example |
|---|---|
| `{Entity}_model.php` | `Student_model.php` |
| `{Entity}_{sub}_model.php` | `Studentfee_model.php`, `Studentsession_model.php` |
| `{Feature}_model.php` | `Examresult_model.php`, `Userpermission_model.php` |

Models are loaded in controllers via:
```php
$this->load->model('Student_model', 'student_model');
// then used as:
$this->student_model->getStudents();
```
