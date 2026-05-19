# Web Gamon — Waste Management and Reporting System

Framework-free **API-first** web application for reporting waste accumulation, assigning cleanup personnel, and monitoring operations via an admin panel.

## Technologies

| Layer | Stack |
|--------|--------|
| Backend | PHP 8+, vanilla, PDO |
| Database | SQLite (`database/waste.db`) |
| Frontend | HTML5, CSS3, vanilla JavaScript |
| API | JSON endpoints, `fetch()` / AJAX |
| Auth | PHP sessions, role-based access |

**Not used:** React, Vue, Angular, Laravel, Symfony, Express, Bootstrap (framework), Composer/npm frontend build.

## Features

- **Citizens:** create reports with priority (low / medium / high / critical), optional photo, track own reports
- **Personnel:** view open tasks, self-assign, update assigned task status
- **Admins:** user/role management, categories & areas CRUD, report edit/soft-delete, personnel assignment, analytics, activity log, SLA filters, CSV/JSON import & export
- **Security:** CSRF (`X-CSRF-Token`), XSS escaping, prepared statements, upload validation, session hardening

## Folder structure

```
web_gamon/
├── admin/           # Admin UI pages
├── api/             # JSON API endpoints
├── assets/          # CSS, JS
├── citizen/         # Citizen portal
├── config/          # config.php (BASE_URL, paths)
├── core/            # Auth, DB, Csrf, Validator, …
├── database/        # schema.sql, seed.sql, waste.db
├── includes/        # header.php, footer.php
├── personnel/       # Personnel portal
├── scripts/         # CLI helpers (admin, demo seed)
├── uploads/         # Report images (.htaccess protected)
├── index.php, login.php, register.php, dashboard.php, account.php
└── README.md
```

## Installation (XAMPP)

1. Copy project to `C:\xampp\htdocs\web_gamon\`
2. Ensure **PHP 8+** is enabled in XAMPP
3. Set `BASE_URL` in `config/config.php` (default: `/web_gamon/`)
4. Open `http://localhost/web_gamon/`
5. SQLite file `database/waste.db` is created automatically on first request

### Fresh database with demo data

Delete `database/waste.db` and reload the site **or** run:

```bash
C:\xampp\php\php.exe scripts\seed_demo.php
```

### Create / reset admin

```bash
C:\xampp\php\php.exe scripts\create_admin.php
C:\xampp\php\php.exe scripts\create_admin.php "YourSecurePassword"
```

Default seeded admin:

| Field | Value |
|--------|--------|
| Email | `asliuzar4@gmail.com` |
| Password | `Demo123!` (change after first login) |
| Role | `admin` |

### Demo accounts (after seed)

| Role | Email | Password |
|------|--------|----------|
| Admin | asliuzar4@gmail.com | Demo123! |
| Personnel | personnel1@demo.local | Demo123! |
| Personnel | personnel2@demo.local | Demo123! |
| Citizen | citizen1@demo.local | Demo123! |
| Citizen | citizen2@demo.local | Demo123! |
| Citizen | citizen3@demo.local | Demo123! |

## User roles

| Role | Access |
|------|--------|
| **citizen** | Own reports only |
| **personnel** | Open reports + own assignments; status updates on assigned reports |
| **admin** | Full system management, analytics, import/export |

Public registration always creates **citizen** accounts. Admins promote roles via **Admin → Users**.

## API overview

All state-changing endpoints require **POST** + header:

```
X-CSRF-Token: <token from window.CSRF_TOKEN>
```

Standard JSON response:

```json
{ "ok": true, "items": [] }
{ "ok": false, "error": "message" }
```

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `api/auth/login.php` | Login |
| POST | `api/auth/register.php` | Register (citizen) |
| POST | `api/auth/logout.php` | Logout |
| GET | `api/auth/session.php` | Current user |
| POST | `api/auth/delete-account.php` | Self-delete |

### Reports

| Method | Endpoint | Role |
|--------|----------|------|
| GET | `api/reports/list.php` | Logged in (filters below) |
| GET | `api/reports/detail.php` | Logged in |
| POST | `api/reports/create.php` | citizen (+) — optional `priority` |
| POST | `api/reports/assign.php` | admin, personnel |
| POST | `api/reports/update-status.php` | admin, personnel |
| POST | `api/reports/update-assignment-progress.php` | personnel (own assignments) |
| POST | `api/reports/update.php` | admin — includes `priority` |
| POST | `api/reports/delete.php` | admin (soft) |
| GET | `api/reports/assignment-history.php` | admin |
| GET | `api/reports/timeline.php` | Logged in (report access) |

### Categories & areas

| POST | `api/categories/create.php` | admin |
| POST | `api/categories/update.php` | admin |
| POST | `api/categories/delete.php` | admin |
| GET | `api/categories/list.php` | public |

(Same pattern for `api/areas/*`.)

### Analytics

| Method | Endpoint | Role |
|--------|----------|------|
| GET | `api/analytics/summary.php` | admin |
| GET | `api/analytics/distribution.php` | admin |
| GET | `api/analytics/by-area.php`, `by-category.php`, `cleanest-dirtiest.php` | admin |
| GET | `api/admin/activity-log.php` | admin |

### Import / export

| POST | `api/imports/json.php`, `api/imports/csv.php` | admin |
| GET | `api/exports/csv.php`, `api/exports/json.php` | admin |

Admin UI: **Admin → Import Data**.

## Security measures

- **SQL injection:** PDO prepared statements only
- **XSS:** `e()` in PHP; `textContent` / DOM APIs in JS; `DomSafe` helpers
- **CSRF:** session token via `X-CSRF-Token` on all POST APIs
- **Passwords:** `password_hash()` / `password_verify()`
- **Uploads:** MIME/size checks; random filenames; `uploads/.htaccess` blocks script execution
- **Authorization:** `Auth::requireRole()` on APIs; page-level role redirects
- **Soft delete:** users and reports; deleted users masked in listings

## Report priority

Each report has a **priority** (separate from workflow **status**):

| Value | Default | UI |
|-------|---------|-----|
| `low` | | green badge |
| `medium` | yes | blue badge |
| `high` | | amber badge |
| `critical` | | red badge |

- Citizens choose priority on **New report**.
- Admins view and edit priority on **Report detail** and see badges on **Reports** list.
- Personnel see priority badges on **Open reports** and **Assigned tasks**.

Invalid priority on create/update returns HTTP **422**.

## Report list filters (admin & API)

`GET api/reports/list.php` supports query parameters (all optional, combined with AND):

| Parameter | Description |
|-----------|-------------|
| `status` | `open`, `assigned`, `in_progress`, `resolved`, `rejected` |
| `category_id` | Category id |
| `area_id` | Area id |
| `priority` | `low`, `medium`, `high`, `critical` |
| `q` | Search in description or report id (LIKE, wildcards stripped) |
| `sla_status` | **Admin only:** `overdue`, `due_soon`, `on_time`, `resolved_late` |
| `page` | Page number (min 1, default 1) |
| `per_page` | Rows per page (min 5, max 50, default 10) |
| `limit` | Legacy alias for `per_page` when `per_page` is omitted (capped at 50) |
| `assigned_to=me` | Personnel/admin: only reports assigned to current user |

**Role scope** (unchanged):

- **citizen** — own reports only
- **personnel** — open reports OR reports assigned to them (unless `assigned_to=me`)
- **admin** — all non-deleted reports

Admin UI: **Admin → Reports** filter bar uses fetch; **Apply filters** / **Reset filters**.

## Report list pagination

`GET api/reports/list.php` returns pagination metadata (backward compatible — `items` unchanged):

```json
{
  "ok": true,
  "items": [],
  "page": 1,
  "per_page": 10,
  "total": 42,
  "total_pages": 5
}
```

- `total` — count after filters + role scope (excludes soft-deleted reports)
- `total_pages` — `ceil(total / per_page)`, or `0` when `total` is 0
- `LIMIT` / `OFFSET` use integer-bound parameters (no SQL injection)

**Defaults:** `page=1`, `per_page=10`. **Bounds:** `per_page` 5–50.

**Admin UI:** pagination bar on **Admin → Reports** (Previous/Next, page label, per-page 10/20/50). Filters reset to page 1.

**Citizen / personnel pages** call `per_page=50` so existing “show all” behaviour is preserved without adding pagination UI yet.

## Personnel assignment progress

Separate from **report status**, each assignment has its own progress:

| `progress_status` | UI label |
|-------------------|----------|
| `not_started` | Not started |
| `in_progress` | In progress |
| `completed` | Completed |

Personnel update via **Assigned Tasks** or `POST api/reports/update-assignment-progress.php` (optional `progress_note`). Admins see progress on report detail (read-only).

## Status workflow

| From | Personnel may go to | Admin may go to |
|------|---------------------|-----------------|
| open | (assign only) | assigned, in_progress, resolved, rejected |
| assigned | in_progress, resolved | open, in_progress, resolved, rejected |
| in_progress | resolved | assigned, open, resolved, rejected |
| resolved / rejected | — | reopen to other states (admin) |

Invalid transitions return HTTP **409**.

## Admin analytics charts

**Page:** `admin/analytics.php`  
**API:** `GET api/analytics/distribution.php` (admin only)

CSS horizontal bar charts (no Chart.js / no framework) show:

| Chart | Data |
|-------|------|
| Status | `open`, `assigned`, `in_progress`, `resolved` |
| Priority | `low`, `medium`, `high`, `critical` |
| Category | report count per category |
| Area | report count per area |

- Only **non-deleted** reports (`is_deleted = 0`) are counted.
- Labels rendered with `textContent` (XSS-safe).
- Loading, empty (“No data available”), and error (toast) states included.
- Responsive: 2 columns desktop, 1 column mobile.

Existing KPI / cleanest-dirtiest endpoints remain unchanged.

## SLA / deadlines

Each active report has a **due_at** deadline from `created_at` and **priority**:

| Priority | SLA |
|----------|-----|
| low | +7 days |
| medium | +3 days |
| high | +24 hours |
| critical | +6 hours |

- **resolved_at** is set when status becomes `resolved`.
- List/detail APIs add: `due_at`, `resolved_at`, `is_overdue`, `is_resolved_late`, `remaining_time`.
- **Priority change:** `due_at` is recalculated from `created_at` only while status is not `resolved` or `rejected` (closed reports keep the historical deadline).
- UI: SLA badges on admin/personnel report lists and report detail.
- Analytics KPIs: overdue count, resolved late, SLA compliance %.

## Report timeline

**API:** `GET api/reports/timeline.php?report_id=` — same RBAC as report detail.

Report detail pages show a vertical **Timeline** built from `activity_logs` (report + assignment events). Labels are human-readable in the UI.

## Activity log (audit trail)

Critical operations are recorded in `activity_logs` via `ActivityLog::write()`. Logging failures are written to PHP `error_log()` and **do not** fail the main API request.

**Admin UI:** `admin/activity-log.php`  
**API:** `GET api/admin/activity-log.php` (admin only)

| Query param | Description |
|-------------|-------------|
| `action` | Exact action filter |
| `entity_type` | `report`, `assignment`, `category`, `area`, `user` |
| `q` | Search in details, actor name, or email |
| `page`, `per_page` | Pagination (`per_page` 5–100, default 20) |

### Logged actions

| Area | Actions |
|------|---------|
| Reports | `report_created`, `report_updated`, `report_deleted`, `report_priority_changed`, `report_status_changed` |
| Assignment | `report_assigned`, `assignment_progress_changed` |
| Categories | `category_created`, `category_updated`, `category_deleted` |
| Areas | `area_created`, `area_updated`, `area_deleted` |
| Users | `user_role_changed`, `user_soft_deleted` |

Details are stored as JSON (e.g. `old_status` / `new_status`, `assigned_to`, `priority`).

## SLA list filter (admin)

`GET api/reports/list.php?sla_status=…` — **admin role only** (403 for others).

| Value | Meaning |
|-------|---------|
| `overdue` | Unresolved and `due_at` &lt; now |
| `due_soon` | Unresolved and due within next 24 hours |
| `on_time` | Unresolved and `due_at` &gt; now + 24 hours |
| `resolved_late` | `resolved` and `resolved_at` &gt; `due_at` |

Admin UI: **Admin → Reports** → **SLA status** dropdown (works with pagination and other filters).

## Final demo guide

### Demo accounts

| Role | Email | Password |
|------|--------|----------|
| Admin | asliuzar4@gmail.com | Demo123! |
| Personnel | personnel1@demo.local, personnel2@demo.local | Demo123! |
| Citizen | citizen1@demo.local … citizen3@demo.local | Demo123! |

### Prepare database

```bash
php scripts/seed_demo.php
```

Re-running seed is safe (`INSERT OR IGNORE` + idempotent `[DEMO-SLA]` reports).

### Recommended demo order

1. **Admin** — Analytics (KPI + charts) → Reports (SLA filter **Overdue only**) → Report detail (timeline, assign) → Activity log  
2. **Personnel** — Open reports → Assign → Assigned tasks (progress + status)  
3. **Citizen** — New report (priority + photo) → My reports  

Full script: [`docs/DEMO_SCRIPT.md`](docs/DEMO_SCRIPT.md)  
Manual QA checklist: [`docs/TEST_CHECKLIST.md`](docs/TEST_CHECKLIST.md)

### Course compliance (typical requirements)

| Requirement | Implementation |
|-------------|----------------|
| Web app with DB | PHP + SQLite + PDO |
| User roles | citizen, personnel, admin |
| CRUD | Reports, categories, areas, users (admin) |
| Security | CSRF, RBAC, prepared statements, XSS-safe output |
| No heavy frameworks | Vanilla PHP / JS / CSS |
| Documentation | README + demo script + test checklist |

### Known limitations

- No email, push, or WebSocket notifications  
- No map / geolocation UI  
- Pagination UI on admin reports only (citizen/personnel use `per_page=50`)  
- Single SQLite file (not suited for high concurrent write load)  
- English UI (no i18n layer)  
- Export/import does not apply list filters  

## Development scripts

```bash
php scripts/seed_demo.php
php scripts/create_admin.php [password]
php scripts/test_sla_filter.php
php scripts/test_sla_timeline.php
php scripts/test_pagination.php
php scripts/test_analytics_distribution.php
php scripts/test_activity_log.php
php scripts/validate_stages_1_2.php
```

## License / course

Built for academic coursework — Waste Management and Reporting System (Web Gamon).
