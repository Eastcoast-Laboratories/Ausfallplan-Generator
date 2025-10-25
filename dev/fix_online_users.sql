-- Fix für Online-Server: User haben keine OrganizationUser-Einträge
-- Problem: "Sie müssen einer Organisation angehören, um Kinder zu erstellen."
-- 
-- Dieses Script migriert alle bestehenden User zur neuen OrganizationUsers-Struktur
-- WICHTIG: NUR ausführen wenn die Migrationen bereits gelaufen sind!

-- 1. Prüfen ob organization_users Tabelle existiert
-- SELECT * FROM organization_users LIMIT 1;

-- 2. Alle User anzeigen die KEINE OrganizationUser-Einträge haben
SELECT u.id, u.email, u.is_system_admin
FROM users u
LEFT JOIN organization_users ou ON u.id = ou.user_id
WHERE ou.id IS NULL;

-- 3. Für JEDEN User ohne Organization-Zuordnung einen Eintrag erstellen
-- WICHTIG: Passe organization_id = 1 an, falls deine Organization eine andere ID hat!

INSERT INTO organization_users (user_id, organization_id, role, is_primary, joined_at, created, modified)
SELECT 
    u.id as user_id,
    1 as organization_id,  -- ACHTUNG: Prüfe die richtige Organization-ID!
    CASE 
        WHEN u.is_system_admin = 1 THEN 'org_admin'
        ELSE 'editor'
    END as role,
    1 as is_primary,
    datetime('now') as joined_at,
    datetime('now') as created,
    datetime('now') as modified
FROM users u
LEFT JOIN organization_users ou ON u.id = ou.user_id
WHERE ou.id IS NULL;

-- 4. Verifizieren: Alle User sollten jetzt einen OrganizationUser-Eintrag haben
SELECT 
    u.id,
    u.email,
    u.is_system_admin,
    ou.role,
    ou.organization_id,
    o.name as organization_name
FROM users u
LEFT JOIN organization_users ou ON u.id = ou.user_id
LEFT JOIN organizations o ON ou.organization_id = o.id
ORDER BY u.email;

-- Erwartetes Ergebnis:
-- Alle User haben einen organization_users Eintrag
-- ruben.barkow@eclabs.de sollte role='org_admin' oder 'editor' haben
