-- Setup test users for E2E tests

-- Create test organization if not exists
INSERT OR IGNORE INTO organizations (id, name, created, modified) 
VALUES (999, 'Test Kita E2E', datetime('now'), datetime('now'));

-- Admin user (password: password123)
INSERT OR REPLACE INTO users (id, organization_id, email, password, role, status, email_verified, created, modified)
VALUES (
  998,
  999,
  'admin@test.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
  'admin',
  'active',
  1,
  datetime('now'),
  datetime('now')
);

-- Editor user
INSERT OR REPLACE INTO users (id, organization_id, email, password, role, status, email_verified, created, modified)
VALUES (
  997,
  999,
  'editor@test.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
  'editor',
  'active',
  1,
  datetime('now'),
  datetime('now')
);

-- Viewer user
INSERT OR REPLACE INTO users (id, organization_id, email, password, role, status, email_verified, created, modified)
VALUES (
  996,
  999,
  'viewer@test.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
  'viewer',
  'active',
  1,
  datetime('now'),
  datetime('now')
);
