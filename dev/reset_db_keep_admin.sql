-- Reset database - Keep only admin user

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Clear all assignment and schedule related data
DELETE FROM assignments;
DELETE FROM schedule_days;
DELETE FROM schedules;

-- Clear waitlist entries
DELETE FROM waitlist_entries;

-- Clear children and related data
DELETE FROM children;
DELETE FROM sibling_groups;

-- Clear organizations (will cascade to user_organizations)
DELETE FROM organizations;

-- Clear all users EXCEPT admin
DELETE FROM users WHERE email != 'admin@demo.kita';

-- Clear user_organizations table completely
DELETE FROM user_organizations;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show what's left
SELECT 'Users left:' as Info;
SELECT id, email, is_system_admin FROM users;

SELECT 'Children left:' as Info;
SELECT COUNT(*) as count FROM children;

SELECT 'Schedules left:' as Info;
SELECT COUNT(*) as count FROM schedules;

SELECT 'Organizations left:' as Info;
SELECT COUNT(*) as count FROM organizations;
