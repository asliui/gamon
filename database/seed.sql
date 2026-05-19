-- seed.sql — Categories, areas, demo users and sample reports (new database only).
-- Default demo password for all seeded accounts: Demo123!

-- Categories & areas
INSERT OR IGNORE INTO categories (id, name) VALUES
  (1, 'Household Waste'),
  (2, 'Construction Debris'),
  (3, 'Recyclables'),
  (4, 'Hazardous Waste');

INSERT OR IGNORE INTO areas (id, name) VALUES
  (1, 'Central'),
  (2, 'North'),
  (3, 'South'),
  (4, 'East'),
  (5, 'West');

-- Demo password hash (Demo123!)
-- $2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW

INSERT OR IGNORE INTO users (id, name, email, password_hash, role) VALUES
  (1, 'System Admin', 'asliuzar4@gmail.com', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'admin'),
  (2, 'Personnel One', 'personnel1@demo.local', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'personnel'),
  (3, 'Personnel Two', 'personnel2@demo.local', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'personnel'),
  (4, 'Citizen Ali', 'citizen1@demo.local', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'citizen'),
  (5, 'Citizen Ayse', 'citizen2@demo.local', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'citizen'),
  (6, 'Citizen Mehmet', 'citizen3@demo.local', '$2y$10$LT54LlmirKH.vASxpLHNdeql/A/CAUOYGQDeU8bDEAvfkUrt5ZlfW', 'citizen');

INSERT OR IGNORE INTO reports (id, citizen_id, category_id, area_id, description, status, created_at, updated_at) VALUES
  (1, 4, 1, 1, 'Overflowing bins near the main square.', 'open', datetime('now', '-5 days'), datetime('now', '-5 days')),
  (2, 5, 2, 2, 'Construction debris blocking the sidewalk.', 'open', datetime('now', '-4 days'), datetime('now', '-4 days')),
  (3, 6, 3, 3, 'Recyclables pile-up at collection point.', 'assigned', datetime('now', '-3 days'), datetime('now', '-2 days')),
  (4, 4, 1, 4, 'Illegal dumping in the park area.', 'in_progress', datetime('now', '-2 days'), datetime('now', '-1 days')),
  (5, 5, 4, 5, 'Hazardous containers reported near school.', 'resolved', datetime('now', '-10 days'), datetime('now', '-1 days')),
  (6, 6, 2, 1, 'Rubble after road works not collected.', 'assigned', datetime('now', '-1 days'), datetime('now'));

INSERT OR IGNORE INTO assignments (report_id, personnel_id, assigned_at) VALUES
  (3, 2, datetime('now', '-2 days')),
  (4, 2, datetime('now', '-1 days')),
  (5, 3, datetime('now', '-9 days')),
  (6, 3, datetime('now', '-12 hours'));

INSERT OR IGNORE INTO assignment_history (report_id, old_personnel_id, new_personnel_id, assigned_by, assigned_at) VALUES
  (3, NULL, 2, 1, datetime('now', '-2 days')),
  (4, NULL, 2, 1, datetime('now', '-1 days')),
  (5, NULL, 3, 1, datetime('now', '-9 days')),
  (6, NULL, 3, 1, datetime('now', '-12 hours'));
