# Fee Management Module

## Overview

EduRoot's fee module handles the complete student fee lifecycle: from defining fee structures through to collection, receipts, discounts, and financial reporting.

---

## Concepts

Understanding these five concepts in order makes the rest of the module clear:

```
Fee Type  →  Fee Group  →  Fee Master  →  Student Fee Master  →  Payment
(what)        (bundle)      (amount+date)   (per student)          (receipt)
```

| Concept | Table | Description |
|---|---|---|
| **Fee Type** | `feetype` | The name of a charge: Tuition, Library, Transport, Exam Fee |
| **Fee Group** | `fee_groups` | A named bundle of fee types for a session + class |
| **Fee Group Type** | `fee_groups_feetype` | Amounts and due dates for each type within a group |
| **Fee Master** | `feemasters` | The actual fee schedule assigned to a class/session |
| **Student Fee Master** | `student_fees_master` | One record per student-session, links to all their fees |
| **Student Fee** | `student_fees` | Individual fee line items per student |
| **Student Fee Deposit** | `student_fees_deposite` | Payment receipts — one per payment transaction |

---

## Workflow

### Setup (Admin, once per session)

1. **Admin → Fees → Fee Type** — create types: Tuition, Lab, Library, Transport
2. **Admin → Fees → Fee Group** — create a group: "Annual Fees 2024-25" for Class 10
3. Add fee types to the group with amounts and due dates
4. **Admin → Fees → Fee Master** — assign the group to a class/section

### Enrollment (On student admission or session start)

When a student is enrolled in a session, their fee records are created automatically:
- `student_fees_master` row created
- `student_fees` rows created for each fee type in their assigned group
- Transport fees appended if a route is assigned
- Discounts applied if any discount is assigned to the student

### Collection (Accountant / Admin)

1. Go to **Fees → Collect Fee → Search Student**
2. Select student, see outstanding balance per fee type
3. Enter amount (partial or full payment supported)
4. Select payment mode (cash, cheque, online, bank transfer)
5. System creates a `student_fees_deposite` record
6. Receipt PDF is generated with auto-incremented receipt number
7. WhatsApp/email notification sent if configured

---

## Controllers

| Controller | File | Key Methods |
|---|---|---|
| Fee Type | `admin/Feetype.php` | `index`, `edit`, `delete` |
| Fee Category | `admin/Feecategory.php` | `index`, `edit`, `delete` |
| Fee Group | `admin/Feegroup.php` | `index`, `edit`, `delete` |
| Fee Master | `admin/Feemaster.php` | `index`, `save_data`, `edit`, `assign` |
| Fee Discount | `admin/Feediscount.php` | `index`, `edit`, `assign`, `applydiscount` |
| Fee Reminder | `admin/Feereminder.php` | `setting` |
| Fees Forward | `admin/Feesforward.php` | `index`, `findPreviousBalanceFees` |
| Student Fee | `Studentfee.php` | `index`, `addfee`, `getcollectfee`, `create`, `pdf` |
| Balance Fee | `Balancefees.php` | `index`, `search` |
| Finance Reports | `Financereports.php` | `index`, `search`, `pdf` |
| Online Payment (student) | `user/gateway/{gateway}.php` | `index`, `payment`, `callback` |
| Online Payment (admission) | `onlineadmission/{gateway}.php` | `index`, `callback` |

---

## Models

| Model | File | Key Methods |
|---|---|---|
| Fee Master | `Feemaster_model.php` | `getAll`, `create`, `edit`, `delete` |
| Fee Group | `Feegroup_model.php` | `getAll`, `getWithTypes` |
| Fee Group Type | `Feegrouptype_model.php` | `getByGroup` |
| Student Fee Master | `Studentfeemaster_model.php` | `getByStudentSession`, `create` |
| Student Fee | `Studentfee_model.php` | `getByMaster`, `getBalance`, `collect`, `delete` |
| Fee Discount | `Feediscount_model.php` | `getAll`, `assign`, `applyDiscount` |
| Fee Session Group | `Feesessiongroup_model.php` | `getByStudentSession` |
| Student Applied Discount | `StudentAppliedDiscount_model.php` | `getByMaster`, `add`, `delete` |

---

## Payment Receipt

Receipts are generated as PDF by `Studentfee::pdf()`. The receipt includes:
- School logo and name
- Receipt number (auto-incremented from `fee_receipt_no`)
- Student name, class, section, admission number
- Itemised fee breakdown
- Amount paid, balance remaining
- Payment mode and date
- Collected by (staff name)

Receipt numbers are scoped per school to allow multiple schools in SaaS mode.

---

## Discounts

Discounts can be:
- **Percentage** — e.g. 25% off all fees
- **Fixed amount** — e.g. ₹500 off Lab fee
- **Per fee type** — apply to specific line items only

Assign discounts to students via **Fees → Fee Discount → Assign**. Discounts are recorded in `fees_discounts` and linked per-student in `student_fees_discounts`.

---

## Fee Forwarding

When an academic session ends, any outstanding balance can be forwarded to the next session via **Fees → Fees Forward**. This creates a new fee entry in the next session under a special "Arrears" fee type.

---

## Online Fee Payment

Students/parents can pay fees online through the student portal (`/student/studentfee/`). The payment flow:

```
Student selects fee → selects gateway → redirected to gateway → 
payment processed → gateway posts to /webhooks/{gateway} → 
Webhooks.php marks payment complete → receipt generated → 
notification sent
```

Payment settings per gateway are stored in `payment_settings`, managed via **Admin → Payment Settings**.

---

## Fee Reminders

Fee reminders are sent via the cron job `cron/fees_reminder`. Configuration:
- How many days before due date to send the reminder
- Message type (email / SMS / WhatsApp)
- Stored in `fees_reminder` per school

---

## Financial Reports

Available under **Reports → Finance**:

| Report | Description |
|---|---|
| Fee Collection Report | Total collected by date range, class, or session |
| Balance Fee Report | Outstanding dues by class/student |
| Fee Type Report | Collection breakdown by fee type |
| Payment Mode Report | Cash vs online vs cheque summary |
| Expense Report | Expenses by head and date range |
| Income Report | Non-fee income by head |
| Payroll Report | Staff salary disbursement |

---

## Notes

- Partial payments are supported — a student can pay part of their dues in multiple transactions
- Payment mode options: Cash, Cheque, DD, Bank Transfer, Online (per-gateway)
- Receipt number format is configurable in school settings
- `student_fees_deposite` is NOT soft-deleted — records are permanent for audit integrity
