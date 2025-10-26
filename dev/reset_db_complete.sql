-- Complete database reset - Keep only admin user

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Delete assignments first (depends on schedule_days and children)
TRUNCATE TABLE assignments;

-- 2. Delete waitlist entries (depends on children and schedules)
TRUNCATE TABLE waitlist_entries;

-- 3. Delete schedule_days (depends on schedules)
TRUNCATE TABLE schedule_days;

-- 4. Delete schedules (depends on organizations)
TRUNCATE TABLE schedules;

-- 5. Delete children (depends on sibling_groups and organizations)
TRUNCATE TABLE children;

-- 6. Delete sibling_groups (depends on organizations)
TRUNCATE TABLE sibling_groups;

-- 7. Delete user_organizations (junction table)
TRUNCATE TABLE user_organizations;

-- 8. Delete organizations
TRUNCATE TABLE organizations;

-- 9. Delete all users EXCEPT admin@demo.kita
DELETE FROM users WHERE email != 'admin@demo.kita';

SET FOREIGN_KEY_CHECKS = 1;

-- Verify
SELECT 'Remaining users:' as Status;
SELECT id, email, is_system_admin FROM users;

SELECT 'Tables cleared:' as Status;
SELECT 
  (SELECT COUNT(*) FROM children) as children,
  (SELECT COUNT(*) FROM schedules) as schedules,
  (SELECT COUNT(*) FROM organizations) as organizations,
  (SELECT COUNT(*) FROM sibling_groups) as sibling_groups,
  (SELECT COUNT(*) FROM assignments) as assignments,
  (SELECT COUNT(*) FROM waitlist_entries) as waitlist_entries;
