# EduRoot — Developer Documentation

This folder is the single source of truth for everyone building on or maintaining EduRoot.

---

## How to read this

Start with **architecture** to understand the big picture, then jump to whichever **module** or **schema** section is relevant to what you are building.

```
docs/
├── README.md                     ← you are here
│
├── architecture/
│   ├── overview.md               ← request lifecycle, folder map, conventions
│   └── module-map.md             ← which controller owns which feature
│
├── schema/
│   ├── README.md                 ← schema index + entity-relationship overview
│   ├── 01-core-auth.md           ← users, roles, sessions, settings
│   ├── 02-student.md             ← students, admissions, custom fields
│   ├── 03-academic.md            ← classes, subjects, attendance, timetable
│   ├── 04-fees.md                ← fee types → collection → receipts (full pipeline)
│   ├── 05-exams.md               ← exam groups, results, online exams
│   ├── 06-staff-hrm.md           ← staff, payroll, leave, attendance
│   ├── 07-content.md             ← homework, uploads, chat, video tutorials
│   ├── 08-transport.md           ← vehicles, routes, pickup points
│   ├── 09-library-hostel.md      ← books, issues, hostel rooms
│   ├── 10-notifications.md       ← email, SMS, push, WhatsApp config
│   └── 11-system.md              ← addons, CMS, certificates, system tables
│
└── modules/
    ├── fee-pipeline.md           ← step-by-step fee collection flow
    ├── exam-pipeline.md          ← exam creation → result publish flow
    └── notification-events.md    ← all 28 notification trigger events
```

---

## Quick reference

| I want to… | Go to |
|---|---|
| Understand the codebase from scratch | [architecture/overview.md](architecture/overview.md) |
| Find which controller handles feature X | [architecture/module-map.md](architecture/module-map.md) |
| Look up what columns a table has | [schema/README.md](schema/README.md) |
| Understand how fees work end-to-end | [modules/fee-pipeline.md](modules/fee-pipeline.md) |
| Add a new notification event | [modules/notification-events.md](modules/notification-events.md) |
| Work on exam results | [schema/05-exams.md](schema/05-exams.md) |

---

## Conventions used in these docs

- **PK** = primary key, always `id INT AUTO_INCREMENT`
- **FK** = foreign key, noted as `→ table.id`
- `*` after a column name = nullable / optional
- `enum(...)` values are listed where known
- All timestamps are `DATETIME` unless noted
- "current session" = `sch_settings.session_id` — almost every query is scoped to this
