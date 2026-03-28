# Schema: Expenses & Accounting

8 tables for school expenditure tracking, inventory/stock, and supplier management.

---

## `expense_head`

Expense categories (Salaries, Utilities, Maintenance, Stationery, etc.).

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | |
| `description`* | |

## `expenses`

Individual expense records.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Description |
| `invoice_no`* | Supplier invoice number |
| `date` | DATE |
| `amount` | DECIMAL |
| `exp_head_id` | → `expense_head.id` |
| `documents`* | Path to uploaded invoice/receipt |
| `note`* | |
| `session_id` | → `sessions.id` |
| `feetype_id`* | → `feetype.id` (if expense is linked to a fee head) |
| `class_id`* | → `classes.id` (class-specific expense) |

---

## Inventory / stock tables

### `item_category`

Item categories (Furniture, Electronics, Lab Equipment, Stationery).

### `item_supplier`

Supplier contact records.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Supplier name |
| `contact`* | |
| `address`* | |

### `item`

Item/asset master register.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | |
| `item_category_id` | → `item_category.id` |
| `unit`* | `pcs` `kg` `litre` etc. |
| `description`* | |

### `item_store`

Physical storage locations (Store Room 1, Science Lab Store, etc.).

### `item_stock`

Stock levels per item per store.

| Column | Notes |
|---|---|
| `id` | PK |
| `item_id` | → `item.id` |
| `item_store_id` | → `item_store.id` |
| `quantity` | Current stock level |
| `unit_cost`* | DECIMAL |
| `supplier_id`* | → `item_supplier.id` |
| `purchase_date`* | DATE |

### `item_issue`

Stock issued out (to a class, staff member, or department).

| Column | Notes |
|---|---|
| `id` | PK |
| `item_id` | → `item.id` |
| `item_store_id` | → `item_store.id` |
| `issued_to`* | Description of recipient |
| `quantity` | |
| `issue_date` | DATE |
| `return_date`* | DATE — NULL if not returnable |
