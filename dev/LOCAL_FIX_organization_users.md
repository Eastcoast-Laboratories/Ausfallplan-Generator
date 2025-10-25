# Local Fix: organization_users Error

## Problem
```
Could not describe columns on `organization_users`
Cake\Database\Exception\DatabaseException
```

Beim Login als Admin erschien dieser Fehler.

## Root Cause
Die organization_users Migrationen waren **nicht ausgeführt** (status: down):
- `20251025005900_CreateOrganizationUsersTable` - DOWN ❌
- `20251025025900_RemoveOldUserFields` - DOWN ❌

Die Tabelle `organization_users` existierte nicht in der lokalen Datenbank.

## Solution
```bash
# 1. Migration status prüfen
docker exec ausfallplan-generator bin/cake migrations status

# 2. Migrationen ausführen
docker exec ausfallplan-generator bin/cake migrations migrate

# 3. Cache clearen
docker exec ausfallplan-generator bin/cake cache clear_all
```

## Result
✅ Migration `CreateOrganizationUsersTable` - migrated 0.3381s
✅ Migration `RemoveOldUserFields` - migrated 0.1861s
✅ Tabelle `organization_users` existiert mit 4 Einträgen
✅ Lokale Anwendung läuft auf http://localhost:8080

## Verification
```bash
# Tabelle prüfen
docker exec ausfallplan-db mysql -u ausfallplan -pausfallplan_secret ausfallplan -e "DESCRIBE organization_users;"

# Einträge zählen
docker exec ausfallplan-db mysql -u ausfallplan -pausfallplan_secret ausfallplan -e "SELECT COUNT(*) FROM organization_users;"
# Result: 4 entries
```

## Docker Setup
- **App Container:** ausfallplan-generator (Port 8080)
- **DB Container:** ausfallplan-db (MySQL 8.0, Port 3306)
- **PHPMyAdmin:** ausfallplan-phpmyadmin (Port 8081)

## Important Note
**Lokal vs Server:**
- Dieser Fix war für die **lokale** Docker-Installation
- Server-Änderungen nur wenn explizit gefordert!
- Keywords für Server: "auf dem server", "online", "deploy", "z11.de"

## Date
25.10.2025, 09:15 Uhr
