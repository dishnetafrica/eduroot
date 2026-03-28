# Schema: Fees

26 tables. The most complex module in EduRoot. Read this carefully before touching anything fee-related.

---

## The fee pipeline at a glance

```
CONFIGURATION (set once per session)
─────────────────────────────────────
feetype          "Tuition Fee", "Library Fee"
  └── feecategory   "Monthly", "Annual", "One-time"
        └── feemasters   amount per class per session
              └── fee_groups   bundle multiple feetypes
                    └── fee_groups_feetype   (junction)
                          └── fee_session_groups   assign group to class+session

STUDENT ASSIGNMENT (when student is enrolled)
──────────────────────────────────────────────
fee_session_groups → student_fees_master   (what this student owes)
fees_discounts     → student_applied_discounts   (any waivers)

COLLECTION (when fee is paid)
──────────────────────────────
student_fees   (payment record, one per transaction)
  └── student_fees_deposite   (breakdown of which feetypes were paid)
student_fees_processing   (pending/gateway-in-progress payments)
gateway_ins / gateway_ins_response   (online payment audit trail)
fee_receipt_no   (auto-increment receipt number sequence)
```

---

## Configuration tables

### `feetype`

The atomic fee item.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | e.g. `Tuition Fee`, `Sports Fee` |
| `code`* | Short code |
| `description`* | |

### `feecategory`

How a fee is collected — frequency and type.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | `Monthly` `Quarterly` `Annual` `One-time` |

### `feemasters`

The actual amount for a feetype in a specific class for a specific session.

| Column | Notes |
|---|---|
| `id` | PK |
| `feetype_id` | → `feetype.id` |
| `class_id` | → `classes.id` |
| `session_id` | → `sessions.id` |
| `amount` | DECIMAL(10,2) |
| `description`* | |

**Note:** Amount is defined per class, not per student. Discounts are applied separately.

### `fee_groups`

Bundles multiple fee types into a named group that can be assigned to a class section.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | e.g. `Monthly Fee Pack`, `Annual Charges` |
| `description`* | |
| `session_id` | → `sessions.id` |

### `fee_groups_feetype`

Junction table: which fee types are in which fee group.

| Column | Notes |
|---|---|
| `id` | PK |
| `fee_group_id` | → `fee_groups.id` |
| `feetype_id` | → `feetype.id` |
| `amount` | DECIMAL — can override `feemasters.amount` |
| `due_date`* | DATE — when this fee type is due |

### `fee_session_groups`

Assigns a fee group to a class section for a session. This is the "publish fees for this class" action.

| Column | Notes |
|---|---|
| `id` | PK |
| `fee_group_id` | → `fee_groups.id` |
| `class_section_id` | → `class_sections.id` |
| `session_id` | → `sessions.id` |
| `due_date`* | DATE — overall group due date |

---

## Discount tables

### `fees_discounts`

Discount schemes defined by the school.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | e.g. `Sibling Discount`, `Staff Ward Discount` |
| `code`* | |
| `discount_type` | `percent` / `fixed` |
| `discount` | Amount or percentage value |
| `fee_groups_feetype_id`* | → `fee_groups_feetype.id` (apply to specific fee type only) |

### `student_applied_discounts`

Which discount has been applied to which student.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_fees_master_id` | → `student_fees_master.id` |
| `fees_discount_id` | → `fees_discounts.id` |
| `amount` | Actual discount amount applied |
| `session_id` | → `sessions.id` |

### `student_fees_discounts`

Per-payment-record discount (manual discount at collection time).

---

## Student fee assignment

### `student_fees_master`

The fee ledger for a student in a session — what they owe in total.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `fee_session_group_id` | → `fee_session_groups.id` |
| `amount` | Total amount owed |
| `is_system` | `1` = auto-generated when student enrolled |

---

## Collection tables

### `student_fees`

A fee payment transaction. One row per payment event.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `feemaster_id` | → `student_fees_master.id` |
| `amount` | Amount paid this transaction |
| `amount_discount` | Discount applied |
| `amount_fine` | Fine/late fee added |
| `payment_mode` | `cash` `cheque` `online` `bank_transfer` |
| `date` | DATE — payment date |
| `description`* | Narration / receipt note |
| `created_at` | DATETIME |

### `student_fees_deposite`

Line-item breakdown of a payment — which specific fee types were paid in this transaction.

| Column | Notes |
|---|---|
| `id` | PK |
| `student_fees_master_id` | → `student_fees_master.id` |
| `fee_groups_feetype_id` | → `fee_groups_feetype.id` |
| `amount` | Amount paid for this fee type |

### `student_fees_processing`

Payments initiated online but not yet confirmed (gateway pending state).

| Column | Notes |
|---|---|
| `id` | PK |
| `student_session_id` | → `student_session.id` |
| `amount` | |
| `gateway` | Gateway name string |
| `transaction_id`* | Gateway transaction reference |
| `status` | `pending` `success` `failed` |
| `created_at` | |

### `gateway_ins`

Online payment initiation record (one row when student clicks "Pay Now").

### `gateway_ins_response`

Raw gateway callback/webhook response. Stored for audit. Never delete these.

### `fee_receipt_no`

Sequence table for receipt number generation. One row per school with an auto-incrementing counter. The app reads, increments, and writes back within a transaction to ensure unique receipt numbers.

---

## Reminder & fine tables

### `fees_reminder`

Scheduled fee reminder configurations.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Reminder name |
| `days_before` | INT — days before due date to send |
| `send_sms` | `1` / `0` |
| `send_email` | `1` / `0` |
| `send_whatsapp` | `1` / `0` |
| `session_id` | |

### `cumulative_fine`

Fine rules (daily/monthly after due date).

### `offline_fees_payments`

Cheque / DD payment records awaiting clearance.

---

## Transport fee tables

### `transport_feemaster`

Fee amount per route per session.

### `transport_fees`

Transport fee assignment — which students pay which route fee.

### `student_transport_fees`

Payment records for transport fees (mirrors `student_fees` for transport).

### `student_vehicle_months`

Monthly transport fee payment tracking (which months have been paid for a student).

---

## Income tables

### `income` / `income_head`

Non-fee income (donations, grants, canteen revenue). Separate from student fee collections.

---

## Payment settings

### `payment_settings`

Per-gateway configuration (API keys, secrets, sandbox/live toggle). One row per gateway, stored in the database rather than config files. **Treat this table like a secrets vault.**
