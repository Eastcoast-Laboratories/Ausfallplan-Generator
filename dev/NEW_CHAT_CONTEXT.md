# Ausfallplan-Generator - Projektkontext für neuen Chat

## Projekt-Overview
**Name:** Ausfallplan-Generator  
**Pfad:** `/var/www/Ausfallplan-Generator`  
**Framework:** CakePHP 5.x mit MySQL/MariaDB  
**Docker:** Läuft in Docker (docker-compose.yml in docker/)  
**URL:** http://localhost:8080

## Tech Stack
- **Backend:** CakePHP 5.x (PHP)
- **Database:** MySQL/MariaDB (Hinweis: Alte Migrations für SQLite, wurden umgestellt auf aktuell MySQL)
- **Frontend:** HTML/PHP Templates mit modernem CSS
- **Testing:** Playwright (E2E Tests in tests/e2e/)
- **Server:** Apache in Docker Container

## Aktuelle Features

### User Management
- ✅ Registrierung mit Organisation (Autocomplete via API)
- ✅ Login/Logout (explizite Routes!)
- ✅ 3 Rollen: `admin`, `editor`, `viewer` (read-only)
- ✅ Email & Passwort ändern
- ✅ Password Recovery mit Confirmation Code
- ✅ Email-Verifizierung System

### Organisationen
- ✅ Admin kann Organisationen verwalten (`/admin/organizations`)
- ✅ Organizations haben: `name`, `contact_email`, `contact_phone`, `is_active`, `settings` (JSON)
- ✅ Bei Registration wird Creator's Email automatisch als `contact_email` gesetzt
- ✅ Autocomplete API: `/api/organizations/search.json` (ohne RequestHandler Component)
- ✅ Alle Admin-Seiten komplett auf Deutsch übersetzt

### Schedules (Ausfallpläne)
- Admin sieht alle Schedules mit User/Organization
- Normale User (editor/viewer) sehen nur ihre eigenen
- Schedules haben: `title`, `start_date`, `end_date`, `user_id`, `organization_id`
- Filter nach `user_id` für non-admin users

### Kinder & Geschwistergruppen
- `children` Tabelle mit `organization_id`
- `sibling_groups` für Geschwister-Verwaltung
- `assignments` verknüpft children mit schedules
- Automatische Zuweisung zu "aktivem Schedule" via Session (`activeScheduleId`)

### Waitlist
- Wartelisten-Verwaltung pro Schedule
- Sortierbare Liste

## Datenbank-Schema (Wichtige Tabellen)

### users
```sql
id, email, password, role, organization_id
status (pending/active/inactive)
email_verified, email_verification_token
password_reset_token, password_reset_expires
created, modified
```

### organizations
```sql
id, name, contact_email, contact_phone
is_active (TINYINT), settings (JSON)
created, modified
```

### schedules
```sql
id, title, start_date, end_date
user_id, organization_id
created, modified
```

### children
```sql
id, first_name, last_name, birth_date
organization_id, sibling_group_id
created, modified
```

### assignments
```sql
id, child_id, schedule_id
created, modified
```

## Bekannte Quirks & Best Practices

### Routing ⚠️ KRITISCH!
**Problem:** Admin prefix routes mit `fallbacks()` fangen alle URLs ab!

**Lösung:** Explizite User-Routes MÜSSEN VOR Admin prefix kommen:
```php
// RICHTIG (in routes.php):
$builder->connect('/login', [...], ['_name' => 'login']);
$builder->connect('/logout', [...], ['_name' => 'logout']);
$builder->connect('/forgot-password', [...]);
$builder->connect('/reset-password', [...]);
// ... alle anderen User-Routes ...

// DANN erst:
$builder->prefix('Admin', function (RouteBuilder $routes) {
    $routes->fallbacks(DashedRoute::class);
});
```

### Navigation Links ⚠️ KRITISCH!
**Problem:** `$this->Url->build()` erstellt relative URLs. Im Admin-Bereich wird `/dashboard` zu `/admin/dashboard`!

**Lösung:** Absolute Pfade in Layout verwenden:
```php
// RICHTIG:
<a href="/dashboard">Dashboard</a>
<a href="/logout">Logout</a>

// FALSCH:
<a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>">
```

**Betrifft:**
- `templates/layout/authenticated.php` - Sidebar Navigation
- Alle Links im User-Dropdown
- Organisation-Links in Schedules

### API ohne RequestHandler
Die `Api/OrganizationsController` nutzt KEIN RequestHandler Component (existiert nicht in CakePHP 5):
```php
// Return JSON directly:
$this->response = $this->response
    ->withType('application/json')
    ->withStringBody(json_encode(['organizations' => $organizations]));
return $this->response;
```

### Migrations (für Referenz - aktuell MySQL)
Falls wieder SQLite verwendet wird:
- ⚠️ Immer `BaseMigration` verwenden, nie `AbstractMigration`
- ⚠️ Immer Table API verwenden: `$table->addColumn()->update()`
- ⚠️ **NIEMALS** manuelles `ALTER TABLE` oder `sqlite3` commands
- ⚠️ Bei Problemen: Rollback → Fix → Migrate

### Cache Permissions
Bei Permission-Problemen:
```bash
docker compose -f docker/docker-compose.yml exec -T app bash -c \
  "chown -R www-data:www-data tmp/cache && chmod -R 775 tmp/cache"
```

### File Bearbeitung
**Problem:** Manche Template-Dateien haben Permission denied

**Lösung:** Über Docker Container bearbeiten:
```bash
docker compose -f docker/docker-compose.yml exec -T app sed -i 's/old/new/g' templates/file.php
# oder
docker compose -f docker/docker-compose.yml exec -T app bash < /tmp/script.sh
```

## Docker Commands

### Container Management
```bash
# Container starten
docker compose -f docker/docker-compose.yml up -d

# Shell im Container
docker compose -f docker/docker-compose.yml exec app bash

# MySQL/MariaDB Shell
docker compose -f docker/docker-compose.yml exec db mysql -uausfallplan -pausfallplan_secret ausfallplan

# Logs ansehen
docker compose -f docker/docker-compose.yml logs -f app
```

### CakePHP Commands
```bash
# Migrations
docker compose -f docker/docker-compose.yml exec -T app bin/cake migrations status
docker compose -f docker/docker-compose.yml exec -T app bin/cake migrations migrate
docker compose -f docker/docker-compose.yml exec -T app bin/cake migrations rollback

# Cache leeren (nach Änderungen oft nötig!)
docker compose -f docker/docker-compose.yml exec -T app bin/cake cache clear_all

# Routes anzeigen (sehr nützlich zum Debuggen!)
docker compose -f docker/docker-compose.yml exec -T app bin/cake routes

# Debug: Check if class exists
docker compose -f docker/docker-compose.yml exec -T app php -r \
  "require 'vendor/autoload.php'; var_dump(class_exists('App\\Controller\\Admin\\OrganizationsController'));"
```

### Database Commands
```bash
# Show tables
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "SHOW TABLES;"

# Query specific data
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "SELECT * FROM organizations;" 2>&1 | grep -v Warning

# Update data (wenn nötig für Fixes)
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "UPDATE schedules SET user_id = 2 WHERE organization_id = 2 AND user_id IS NULL;"
```

## Code-Struktur

### Controller
- `src/Controller/` - Normale Controller (Users, Schedules, Children, etc.)
- `src/Controller/Admin/` - Admin-Controller (prefix Admin)
- `src/Controller/Api/` - API-Controller (JSON Responses)

### Templates
- `templates/` - View Templates
- `templates/Admin/` - Admin Views (Organisationen-Verwaltung)
- `templates/layout/authenticated.php` - Haupt-Layout mit Sidebar Navigation

### Middleware
- `src/Middleware/AuthorizationMiddleware.php` - Rolle-basierte Zugriffskontrolle
- `src/Middleware/LocaleMiddleware.php` - Sprach-Handling

### Configuration
- `config/routes.php` - **WICHTIG:** Reihenfolge der Routes beachten!
- `config/app.php` - App Configuration
- `config/Migrations/` - Database Migrations

## Offene TODOs (Backlog)

Aus `dev/TODO.md`:

1. **Email-Bestätigung:** Admin bekommt Mail bei neuem User in seiner Org
2. **Admin Freischaltung:** Admin einer Org kann Users seiner Org freischalten  
3. **Editor Filter:** Editor kann nur eigene Org-Daten bearbeiten (filter implementieren)
4. **Kinder Sortierung in Schedules:**
   - Kinder in einem Schedule müssen extra sortierbar sein (analog zu Waitlists)
   - Diese Sortierung für Report-Verteilung verwenden (nicht mehr Waitlist-Sortierung)

## Gelöste Probleme (zur Info/Referenz)

### Problem: Organization Links zeigten `:id` statt echter IDs
**Ursache:** Array-based routing mit placeholders  
**Lösung:** Direkte URL-Strings: `'/admin/organizations/view/' . $schedule->organization->id`

### Problem: Admin routes conflict mit /logout
**Ursache:** Admin fallback routes fingen `/logout` ab → versuchte `Admin/UsersController::logout()` aufzurufen  
**Lösung:** Explizite User-Routes VOR Admin prefix definieren

### Problem: Navigation hatte admin/ prefix
**Ursache:** `$this->Url->build()` erstellt relative URLs  
**Lösung:** Absolute Pfade verwenden: `<a href="/dashboard">`

### Problem: Cache Permission errors
**Ursache:** tmp/cache/ hatte falsche Owner/Permissions  
**Lösung:** `chown -R www-data:www-data tmp/cache && chmod -R 775 tmp/cache`

### Problem: Organization autocomplete erkannte keine existierenden Orgs
**Ursache:** API gab nur Daten mit Query-Parameter zurück (min 2 Zeichen)  
**Lösung:** Alle Orgs laden (max 50), filter optional

### Problem: Editor User sah keine eigenen Schedules
**Ursache:** Schedules hatten `user_id = NULL`  
**Lösung:** `UPDATE schedules SET user_id = X WHERE organization_id = Y AND user_id IS NULL`

### Problem: is_active Column not found
**Ursache:** Migration war "up" aber Column nicht erstellt  
**Lösung:** Rollback + Table API verwenden statt `execute()`

## Entwickler-Workflow

### Code-Änderungen
1. Dateien im Container bearbeiten oder lokal (volumes sind gemounted)
2. Cache leeren wenn nötig: `bin/cake cache clear_all`
3. Bei Routing-Änderungen: Cache MUSS geleert werden
4. Testen im Browser und/oder mit curl
5. Erst nach erfolgreichen Tests: Git commit

### Testing

#### Browser Testing
```bash
# Als Admin
curl -s -c /tmp/admin.txt -X POST http://localhost:8080/login \
  -d "email=admin@demo.kita" -d "password=84fhr38hf43iahfuX_2"
curl -s -b /tmp/admin.txt http://localhost:8080/admin/organizations

# Als Editor
curl -s -c /tmp/editor.txt -X POST http://localhost:8080/login \
  -d "email=a2@a.de" -d "password=84hbfUb_3dsf"
curl -s -b /tmp/editor.txt http://localhost:8080/schedules
```

#### Playwright E2E Tests
```bash
# Alle Tests
npm test

# Spezifischer Test
npx playwright test tests/e2e/admin-organizations.spec.js

# Mit UI
npx playwright test --ui

# Debug Mode
npx playwright test --debug
```

**Wichtig:** Nach Sprachänderungen müssen User sich ausloggen und neu einloggen!

### Git Workflow
```bash
# Status check
git status

# Add & Commit (mit Timeout wegen Shell-hang Bug!)
git add -A
git commit -m "feat: Description"

# Push NUR wenn explizit vom User gewünscht!
# git push  ← NIEMALS automatisch ausführen!
```

### Deployment
⚠️ **NIEMALS selbständig deployen** außer explizit aufgefordert!

## User-Regeln (KRITISCH - IMMER BEFOLGEN!)

Aus `dev/TODO.md` und Memories:

1. **Nicht selbständig TODO-Liste abarbeiten** - auf Anweisungen warten
2. **Root cause finden, nicht Symptome fixen**
3. **Keine Failsafes/Fallbacks** - bessere Diagnosen implementieren
4. **Keine Errors unterdrücken** - Fehler beheben!
5. **Bei Problemanalyse:** Logging mit eindeutigen Tags hinzufügen
6. **bash file.sh verwenden** statt `chmod +x file.sh`
7. **Kommentare immer auf Englisch**
8. **DRY Prinzip:** Code in Funktionen extrahieren, nicht kopieren
9. **Große Dateien (>2000 Zeilen):** Mit bash commands bearbeiten
10. **Git Commits:** NUR nach erfolgreichen Tests!
11. **Git Push:** NIEMALS ohne explizite Aufforderung
12. **Deployment:** NIEMALS ohne explizite Aufforderung

## Debugging Tipps

### Problem: Seite lädt nicht / 500 Error
```bash
# Check Apache logs
docker compose -f docker/docker-compose.yml logs app | tail -50

# Check CakePHP debug mode
# In webroot sollte Debug-Output sichtbar sein
```

### Problem: Route nicht gefunden
```bash
# Liste alle Routes
docker compose -f docker/docker-compose.yml exec -T app bin/cake routes | grep -i "SEARCH_TERM"

# Cache leeren!
docker compose -f docker/docker-compose.yml exec -T app bin/cake cache clear_all
```

### Problem: Login funktioniert nicht
```bash
# Check user in DB
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "SELECT id, email, password, status, email_verified FROM users WHERE email='test@example.com';"

# Password neu setzen (wenn nötig)
HASH=$(docker compose -f docker/docker-compose.yml exec -T app php -r "echo password_hash('84hbfUb_3dsf', PASSWORD_DEFAULT);")
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "UPDATE users SET password='$HASH', status='active', email_verified=1 WHERE email='test@example.com';"
```

### Problem: "Column not found"
```bash
# Check actual table structure
docker compose -f docker/docker-compose.yml exec -T db mysql -uausfallplan -pausfallplan_secret ausfallplan \
  -e "DESCRIBE table_name;"

# Check migrations status
docker compose -f docker/docker-compose.yml exec -T app bin/cake migrations status
```

## Session & Active Schedule

Das System nutzt Session Storage für "aktiven Schedule":
- Key: `'activeScheduleId'`
- Gesetzt bei: Schedule add/edit
- Gelesen bei: Child add
- Zweck: Automatische Kind-Zuweisung zu Schedule

## Sprach-/Locale-Handling

- Standard: Deutsch (`de_DE`)
- Änderungen via `/language-switcher?locale=de_DE`
- **Wichtig:** Nach Änderung ausloggen & neu einloggen für vollständige Wirkung
- Keine `.po` Dateien - Translations direkt in Templates mit `__('Text')`

---

## Arbeite bitte an folgender Aufgabe:

[Hier die konkrete Aufgabe einfügen]

---

**Letzte Aktualisierung:** 23. Oktober 2025, 23:38 Uhr
**Git Branch:** main (39+ commits ahead of origin)
