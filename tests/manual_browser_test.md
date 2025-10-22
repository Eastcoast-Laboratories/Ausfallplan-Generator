# Manual Browser Translation Test

Dieser Test verifiziert, dass alle deutschen Übersetzungen im Browser korrekt angezeigt werden.

## Vorbereitung

1. **Cache leeren:**
   ```bash
   docker compose -f docker/docker-compose.yml exec app bin/cake cache clear_all
   ```

2. **Ausloggen und neu einloggen:**
   - http://localhost:8080/users/logout
   - http://localhost:8080/login
   - Login: admin@eastcoast-labs.de / password

## Test-Checkliste

### ✅ Login-Seite (http://localhost:8080/login)
- [ ] "Anmelden" (nicht "Login")
- [ ] "E-Mail" Label
- [ ] "Passwort" Label  
- [ ] "Bitte geben Sie Ihre E-Mail und Ihr Passwort ein, um sich anzumelden."
- [ ] Button: "Anmelden"
- [ ] Link: "Neues Konto erstellen"

### ✅ Navigation
- [ ] "Übersicht" (Dashboard)
- [ ] "Kinder" (Children)
- [ ] "Geschwistergruppen" (Sibling Groups)
- [ ] "Ausfallpläne" (Schedules)
- [ ] "Nachrückliste" (Waitlist) - NICHT "Warteliste"

### ✅ Dashboard (http://localhost:8080/dashboard)
- [ ] "Willkommen zurück!"
- [ ] "Schnellzugriff"
- [ ] "Kind hinzufügen"
- [ ] "CSV importieren"

### ✅ Kinder-Liste (http://localhost:8080/children)
- [ ] "Kinder" Überschrift
- [ ] Button: "Neues Kind"
- [ ] Tabellen-Header: "Name", "Status", "Integrativ", "Geschwistergruppe", "Erstellt", "Aktionen"
- [ ] Status: "Aktiv" / "Inaktiv"
- [ ] "Bearbeiten" Link
- [ ] "Löschen" Link

### ✅ Kind hinzufügen (http://localhost:8080/children/add)
- [ ] "Kind hinzufügen" Überschrift
- [ ] Label: "Name"
- [ ] Label: "Aktiv"
- [ ] Label: "Integrationskind"
- [ ] Label: "Geschwistergruppe"
- [ ] Dropdown: "(Keine Geschwistergruppe)"
- [ ] Button: "Absenden"

### ✅ Ausfallpläne (http://localhost:8080/schedules)
- [ ] "Ausfallpläne" Überschrift
- [ ] Button: "Neuer Ausfallplan"
- [ ] Tabellen-Header: "Titel", "Beginnt am", "Endet am", "Status", "Erstellt", "Aktionen"
- [ ] Button: **"Ausfallplan generieren"** (NICHT "Generate List")
- [ ] Button: "Kinder verwalten"
- [ ] Link: "Bearbeiten"
- [ ] Link: "Löschen"

### ✅ Nachrückliste (http://localhost:8080/waitlist)
- [ ] Dropdown: "Ausfallplan wählen"
- [ ] Option: "-- Ausfallplan wählen --"
- [ ] "Verfügbare Kinder"
- [ ] "Kinder auf der Nachrückliste" (NICHT "Warteliste")
- [ ] "(Ziehen zum Sortieren)"

### ✅ Geschwistergruppen (http://localhost:8080/sibling-groups)
- [ ] "Geschwistergruppen" Überschrift
- [ ] Button: "Neue Geschwistergruppe"

### ✅ User Menu (Avatar anklicken)
- [ ] "Einstellungen"
- [ ] "Mein Konto"
- [ ] "Abmelden"

### ✅ Profil (http://localhost:8080/users/profile)
- [ ] "Profil-Einstellungen"
- [ ] "Konto-Informationen"
- [ ] Label: "E-Mail-Adresse"
- [ ] Label: "Rolle"
- [ ] "Passwort ändern"
- [ ] Button: "Änderungen speichern"

## Kritische Punkte

### ❌ KEIN ENGLISCH erlaubt:
- ❌ "Generate List" oder "Generate Report"
- ❌ "Waitlist" 
- ❌ "Children"
- ❌ "New Child"
- ❌ "Manage Children"
- ❌ "Edit", "Delete", "Submit", "Save", "Cancel"
- ❌ "Login", "Email", "Password"

### ✅ Muss DEUTSCH sein:
- ✅ "Ausfallplan generieren"
- ✅ "Nachrückliste"
- ✅ "Kinder"
- ✅ "Neues Kind"
- ✅ "Kinder verwalten"
- ✅ "Bearbeiten", "Löschen", "Absenden", "Speichern", "Abbrechen"
- ✅ "Anmelden", "E-Mail", "Passwort"

## Nach Session-Neustart testen!

**WICHTIG:** Cache clear + Logout + Login ist notwendig, damit alle Übersetzungen geladen werden!

```bash
docker compose -f docker/docker-compose.yml exec app bin/cake cache clear_all
```

Dann: http://localhost:8080/users/logout und neu einloggen!
