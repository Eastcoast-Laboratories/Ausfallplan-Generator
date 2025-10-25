# PHPUnit Setup und Probleme

## Problem auf Server
PHPUnit Tests können auf dem Produktionsserver nicht laufen wegen:
```
SQLSTATE[HY000] [1044] Access denied for user 'ausfallplan_generator'@'localhost' to database 'ausfallplan_generator_test'
```

## Benötigt
1. **Test-Datenbank erstellen** (braucht MySQL root):
   ```sql
   CREATE DATABASE ausfallplan_generator_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON ausfallplan_generator_test.* TO 'ausfallplan_generator'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Test-Config in app_local.php** (bereits hinzugefügt):
   ```php
   'test' => [
       'className' => Connection::class,
       'driver' => Mysql::class,
       // ... same settings as 'default'
       'database' => 'ausfallplan_generator_test',
   ],
   ```

## Lösung
Tests lokal ausführen wo wir MySQL-Root-Rechte haben, oder Server-Admin bitten, die Test-DB zu erstellen.

## Test-Dateien die geprüft werden müssen
20 PHPUnit Tests vorhanden in `/tests/TestCase/`:
1. ApplicationTest.php
2. Controller/Admin/SchedulesAccessTest.php
3. Controller/Api/OrganizationsControllerTest.php
4. Controller/AuthenticationFlowTest.php
5. Controller/ChildrenControllerTest.php
6. Controller/PagesControllerTest.php
7. Controller/PermissionsTest.php
8. Controller/RegistrationNavigationTest.php
9. Controller/SchedulesControllerCapacityTest.php
10. Controller/SchedulesControllerPermissionsTest.php
11. Controller/SchedulesControllerTest.php
12. Controller/SiblingGroupsControllerTest.php
13. Controller/UsersControllerTest.php
14. Integration/NavigationVisibilityTest.php
15. Model/Table/OrganizationUsersTableTest.php
16. Service/ReportServiceTest.php
17. Service/RulesServiceTest.php
18. Service/ScheduleBuilderTest.php
19. Service/WaitlistServiceTest.php
20. View/AuthenticatedLayoutTest.php

## Wichtigste Tests (müssen nach organization_users Migration gefixt werden)
- **AuthenticationFlowTest.php** - Login/Logout mit neuer User-Struktur
- **PermissionsTest.php** - Rechte-System mit organization_users
- **OrganizationUsersTableTest.php** - Neues Join-Table
- **SchedulesControllerPermissionsTest.php** - Access Control
- **RegistrationNavigationTest.php** - Neue Registration mit requested_role

Diese Tests müssen wahrscheinlich angepasst werden, weil das User-System von `role` auf `organization_users` umgestellt wurde.
