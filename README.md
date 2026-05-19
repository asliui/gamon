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

- **Citizens:** create reports (with optional photo), track own reports
- **Personnel:** view open tasks, self-assign, update assigned task status
- **Admins:** user/role management, categories & areas CRUD, report edit/soft-delete, personnel assignment, analytics, CSV/JSON import & export
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
| GET | `api/reports/list.php` | Logged in |
| GET | `api/reports/detail.php` | Logged in |
| POST | `api/reports/create.php` | citizen (+) |
| POST | `api/reports/assign.php` | admin, personnel |
| POST | `api/reports/update-status.php` | admin, personnel |
| POST | `api/reports/update-assignment-progress.php` | personnel (own assignments) |
| POST | `api/reports/update.php` | admin |
| POST | `api/reports/delete.php` | admin (soft) |
| GET | `api/reports/assignment-history.php` | admin |

### Categories & areas

| POST | `api/categories/create.php` | admin |
| POST | `api/categories/update.php` | admin |
| POST | `api/categories/delete.php` | admin |
| GET | `api/categories/list.php` | public |

(Same pattern for `api/areas/*`.)

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

## Personnel assignment progress

Separate from **report status**, each assignment has its own progress:

| `progress_status` | UI label |
|-------------------|----------|
| `not_started` | Yapılmadı |
| `in_progress` | Yapılıyor |
| `completed` | Yapıldı |

Personnel update via **Assigned Tasks** or `POST api/reports/update-assignment-progress.php` (optional `progress_note`). Admins see progress on report detail (read-only).

## Status workflow

| From | Personnel may go to | Admin may go to |
|------|---------------------|-----------------|
| open | (assign only) | assigned, in_progress, resolved, rejected |
| assigned | in_progress, resolved | open, in_progress, resolved, rejected |
| in_progress | resolved | assigned, open, resolved, rejected |
| resolved / rejected | — | reopen to other states (admin) |

Invalid transitions return HTTP **409**.

## Presentation flow (demo script)

1. **Login as admin** (`asliuzar4@gmail.com` / `Demo123!`)
2. **Admin Dashboard** — show KPIs (seeded reports)
3. **Analytics** — charts / cleanest-dirtiest areas
4. **Categories / Areas** — create or edit inline
5. **Reports** → open report detail → **Assign personnel**
6. **Assignment history** table on same page
7. **Logout** → login as `personnel1@demo.local`
8. **Assigned reports** → set status `in_progress` → `resolved`
9. **Logout** → login as `citizen1@demo.local`
10. **New report** with photo → **My reports**
11. (Optional) **Import/Export** on admin panel

## Development scripts

```bash
# Apply demo seed to existing DB
php scripts/seed_demo.php

# Create or reset admin password
php scripts/create_admin.php [password]
```

## License / course

Built for academic coursework — Waste Management and Reporting System (Web Gamon).
