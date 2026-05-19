# Web Gamon — Live Demo Script (3–5 minutes)

Use a fresh browser profile or incognito window. Base URL: `http://localhost/web_gamon/`

**Default password (all demo accounts):** `Demo123!`

---

## Before you start

1. Ensure XAMPP Apache + PHP 8+ are running.
2. Run once if needed: `php scripts/seed_demo.php`
3. Keep this tab order ready: Admin → Personnel → Citizen.

---

## 1. Admin overview (90 s)

**Login:** `asliuzar4@gmail.com` / `Demo123!`

1. **Dashboard** → **Open Admin Control Panel**
2. **Analytics** — point out KPI cards (overdue, resolved late, SLA compliance %) and CSS bar charts (status, priority, category, area).
3. **Reports** — apply filters:
   - **SLA status → Overdue only** (show critical overdue demo row)
   - Reset → **Priority → Critical**
4. Open a report **View** → show:
   - Priority badge + SLA badge
   - **Timeline** (audit events)
   - Assign personnel (if unassigned demo row)
5. **Activity log** — scroll recent `report_created`, `report_status_changed`, etc.

**Talking point:** API-first, RBAC, CSRF on writes, soft delete, no frameworks.

---

## 2. Personnel workflow (60 s)

**Logout** → **Login:** `personnel1@demo.local` / `Demo123!`

1. **Open reports** — pick an open task → **Assign to me**
2. **Assigned tasks** — update **Work status** + note → **Update progress**
3. **Start work (report)** / **Mark resolved (report)** on one item
4. Open **View** on a report → show **Timeline** + SLA badge

**Talking point:** Report status vs assignment progress are separate.

---

## 3. Citizen flow (45 s)

**Logout** → **Login:** `citizen1@demo.local` / `Demo123!`

1. **New report** — choose priority, category, area, optional photo
2. **My reports** — SLA badge on list
3. Open own report detail (cannot see others’ reports)

---

## 4. Optional closing (30 s)

Back to **admin** → **Import Data** or **Export CSV** (one click each).

---

## Features to emphasize

| Area | Highlight |
|------|-----------|
| Security | CSRF header, prepared statements, upload validation |
| SLA | Priority-based deadlines, overdue / due soon filters |
| Audit | Activity log + per-report timeline |
| UX | Dark theme, filters, pagination, toast notifications |

---

## Backup plan (if something fails)

| Issue | Action |
|-------|--------|
| Empty reports list | Run `php scripts/seed_demo.php` and refresh |
| Login fails | Run `php scripts/create_admin.php Demo123!` |
| 403 / CSRF on POST | Hard refresh page (new CSRF token in header) |
| Upload fails | Check `uploads/` writable; max size in PHP config |
| Charts empty | Ensure at least one non-deleted report exists |
| SLA badges missing | Re-run seed; open report created after SLA migration |

---

## Demo accounts

| Role | Email |
|------|--------|
| Admin | asliuzar4@gmail.com |
| Personnel | personnel1@demo.local, personnel2@demo.local |
| Citizen | citizen1@demo.local, citizen2@demo.local, citizen3@demo.local |

SLA showcase reports are tagged `[DEMO-SLA]` in the description (safe to re-seed).
