# Web Gamon — Final Test Checklist

Mark each item after manual verification. Run CLI helpers where noted.

**Setup:** `http://localhost/web_gamon/` · Password: `Demo123!` · Seed: `php scripts/seed_demo.php`

---

## Auth

- [ ] Register new citizen account
- [ ] Login with valid credentials
- [ ] Login with invalid credentials shows error
- [ ] Logout clears session
- [ ] Role redirect: citizen → citizen portal, personnel → personnel, admin → admin
- [ ] Soft-delete account (self) — cannot login again
- [ ] Deleted user email masked in admin lists

---

## Citizen

- [ ] Create report with priority (low / medium / high / critical)
- [ ] Create report with valid image upload
- [ ] Reject invalid upload (wrong type / too large)
- [ ] My reports list shows only own reports
- [ ] SLA badge visible on list
- [ ] Report detail loads for own report
- [ ] Cannot access another citizen’s report (403 or error)
- [ ] Timeline visible on own report detail

---

## Personnel

- [ ] Open reports list loads
- [ ] Assign to me on open report
- [ ] Assigned tasks list shows only own assignments
- [ ] Update assignment progress (not_started → in_progress → completed)
- [ ] Update report status (assigned → in_progress → resolved)
- [ ] SLA badge on lists
- [ ] Report detail + timeline for assigned report
- [ ] Cannot update unassigned report status

---

## Admin

- [ ] Dashboard / admin home loads
- [ ] Analytics KPIs load (including SLA metrics)
- [ ] Distribution charts render (status, priority, category, area)
- [ ] Reports list with status filter
- [ ] Reports list with priority / category / area filters
- [ ] Reports search (`q`) by description or ID
- [ ] **SLA status filter:** overdue, due soon, on time, resolved late
- [ ] Pagination (prev/next, per page 10/20/50)
- [ ] Report detail: edit priority, assign personnel, status change
- [ ] Assignment history table
- [ ] Timeline on report detail
- [ ] Activity log page + filters + pagination
- [ ] Categories CRUD
- [ ] Areas CRUD
- [ ] Users list + role update
- [ ] Soft-delete report — hidden from lists
- [ ] Import JSON/CSV (admin)
- [ ] Export CSV/JSON/HTML

---

## Security

- [ ] POST without `X-CSRF-Token` rejected
- [ ] Admin API returns 403 for citizen/personnel
- [ ] Citizen cannot list other users’ reports (IDOR)
- [ ] Personnel cannot change admin-only fields
- [ ] `sla_status` filter returns 403 for non-admin
- [ ] Upload path does not execute PHP (`uploads/.htaccess`)
- [ ] XSS: user input shown via `textContent` / `e()` in PHP
- [ ] Soft-deleted reports excluded from `api/reports/list.php`

---

## CLI regression scripts

- [ ] `php scripts/validate_stages_1_2.php`
- [ ] `php scripts/test_pagination.php`
- [ ] `php scripts/test_analytics_distribution.php`
- [ ] `php scripts/test_activity_log.php`
- [ ] `php scripts/test_sla_timeline.php`
- [ ] `php scripts/test_sla_filter.php`

---

## SLA & timeline

- [ ] Critical report shows “Due in Xh” or overdue badge
- [ ] Overdue filter shows only unresolved past-due reports
- [ ] Resolved late filter shows resolved where `resolved_at > due_at`
- [ ] Priority change recalculates `due_at` (non-closed reports)
- [ ] Timeline events after create / assign / status change

---

## UI / UX

- [ ] Page titles consistent (English)
- [ ] Empty states show helpful message (not blank table)
- [ ] Mobile nav toggle works
- [ ] No stray `console.log` in production pages
- [ ] Table horizontal scroll on narrow screens (admin reports)

---

## Sign-off

| Tester | Date | Notes |
|--------|------|-------|
| | | |
