# Schema: Transport

5 tables for school vehicle management and student pick-up/drop routing.

---

## `vehicles`

| Column | Notes |
|---|---|
| `id` | PK |
| `vehicle_no` | Registration number |
| `vehicle_model` | |
| `driver_name` | |
| `driver_contact` | |
| `note`* | |
| `is_active` | |

## `transport_route`

Named routes (e.g. "North Route", "Station Road Route").

| Column | Notes |
|---|---|
| `id` | PK |
| `route_title` | |
| `fare` | DECIMAL — base route fare |
| `vehicle_id` | → `vehicles.id` |

## `vehicle_routes`

Links vehicles to routes (a vehicle can cover multiple routes on rotation).

## `route_pickup_point`

Stops along a route.

| Column | Notes |
|---|---|
| `id` | PK |
| `route_id` | → `transport_route.id` |
| `pickup_point_id` | → `pickup_point.id` |
| `distance`* | km from school |

## `pickup_point`

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Stop name |

---

**Student ↔ Transport link:** Stored in `students.hostel_room_id` (for hostel) and in `student_transport_fees` / `student_vehicle_months` for transport. The student's assigned pickup point is stored on their profile, not in a separate junction table.

---
---

# Schema: Library & Hostel

6 tables.

---

## Library

### `books`

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | Book title |
| `isbn`* | |
| `author`* | |
| `publisher`* | |
| `qty` | INT — total copies |
| `available`* | Computed or stored available copies |
| `price`* | DECIMAL |
| `rack_no`* | Physical location |
| `subject_id`* | → `subjects.id` |

### `book_issues`

| Column | Notes |
|---|---|
| `id` | PK |
| `book_id` | → `books.id` |
| `member_id` | → `libarary_members.id` |
| `issue_date` | DATE |
| `duereturn_date` | DATE |
| `return_date`* | DATE — NULL = still issued |
| `is_returned` | `0` / `1` |
| `fine`* | DECIMAL — late return fine |

### `libarary_members`

Students and staff registered as library members.

| Column | Notes |
|---|---|
| `id` | PK |
| `member_id` | → `students.id` or `staff.id` |
| `member_type` | `student` / `staff` |
| `membership_no` | |
| `is_active` | |

---

## Hostel

### `room_types`

Hostel room categories (Single, Double, Dormitory).

| Column | Notes |
|---|---|
| `id` | PK |
| `type` | |
| `cost_per_month`* | DECIMAL |
| `no_of_beds` | |

### `hostel`

Hostel buildings.

| Column | Notes |
|---|---|
| `id` | PK |
| `name` | |
| `type` | `Boys` / `Girls` |
| `address`* | |
| `intake`* | Total capacity |

### `hostel_rooms`

Individual rooms within a hostel.

| Column | Notes |
|---|---|
| `id` | PK |
| `hostel_id` | → `hostel.id` |
| `room_type_id` | → `room_types.id` |
| `room_no` | |
| `no_of_beds` | |

**Student ↔ Hostel link:** `students.hostel_room_id` → `hostel_rooms.id`. Assigned at admission or via the student edit screen.
