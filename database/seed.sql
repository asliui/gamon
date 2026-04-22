-- seed.sql
-- Minimal seed data for first run.

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

