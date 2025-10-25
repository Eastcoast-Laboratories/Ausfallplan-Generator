# Fix: "Could not describe columns on `organization_users`"

## Problem
```
Could not describe columns on `organization_users` 
Cake\Database\Exception\DatabaseException
CORE/src/Database/Schema/MysqlSchemaDialect.php at line 127
```

## Root Cause
**Table-Files lagen im falschen Verzeichnis!**

Nach einem `scp` Upload wurden Files im falschen Ordner platziert:
```
src/Model/Entity/OrganizationsTable.php  ← FALSCH!
src/Model/Entity/UsersTable.php         ← FALSCH!
```

CakePHP konnte die Table-Classes nicht finden, weil sie in `Entity/` statt `Table/` lagen.

## Fix
Files verschoben:
```bash
cd src/Model/Entity
mv OrganizationsTable.php ../Table/
mv UsersTable.php ../Table/
```

## Richtige Struktur
```
src/Model/
├── Entity/
│   ├── Organization.php        ← Entity
│   ├── OrganizationUser.php    ← Entity
│   ├── User.php                ← Entity
│   └── ...
└── Table/
    ├── OrganizationsTable.php   ← Table  ✅
    ├── OrganizationUsersTable.php ← Table ✅
    ├── UsersTable.php           ← Table  ✅
    └── ...
```

## Verifikation
```bash
# Cache clear
bin/cake cache clear_all
rm -rf tmp/cache/models/* tmp/cache/persistent/*

# Verify Tables existieren
ls -la src/Model/Table/ | grep Organization
# OrganizationsTable.php ✅
# OrganizationUsersTable.php ✅
```

## Status
✅ **FIXED** - Files sind jetzt im richtigen Verzeichnis
✅ MySQL Tabelle existiert und hat korrekte Struktur
✅ Cache gecleared

Die App sollte jetzt laufen ohne "Could not describe columns" Fehler.
