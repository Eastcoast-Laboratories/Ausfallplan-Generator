SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM assignments;
DELETE FROM waitlist_entries;
DELETE FROM schedule_days;
DELETE FROM schedules;
DELETE FROM children;
DELETE FROM sibling_groups;
DELETE FROM organization_users;
DELETE FROM organizations;
DELETE FROM users WHERE email != 'admin@demo.kita';

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Database Reset Complete' as Status;
SELECT COUNT(*) as remaining_users FROM users;
SELECT COUNT(*) as remaining_children FROM children;
SELECT COUNT(*) as remaining_organizations FROM organizations;
SELECT COUNT(*) as remaining_schedules FROM schedules;
