# TODO

## Current Sprint - Completed! 🎉

- [x] Admin kann alle Schedules/Ausfallpläne sehen (mit User/Org-Spalten) - ✅ 23.10. 22:50
- [x] Admin kann Organisationen verwalten - ✅ 23.10. 23:00
- [x] Playwright Test: Admin login und Berechtigungen testen - ✅ 23.10. 23:00
- [x] Email und Passwort ändern - ✅ 23.10. 23:15
- [x] Password recovery mit Konfirmationscode - ✅ 23.10. 23:15
- [x] Organization Autocomplete bei Registration - ✅ 23.10. 23:20
- [x] Viewer Role (Read-Only) - ✅ 23.10. 23:20
- [x] organization_id in children table - ✅ 23.10. 23:20

## Backlog

- Erstelle mir eine möglichkeit die db mit phpmyadmin zugreifen

1. children/add soll auch das geschlecht und Geburtsdatum festgelegt werden können (neues Feld in der DB beide optional)
2. http://localhost:8080/sibling-groups/delete/1 geht noch nicht (loeschen)
3. eine Organisation kann mehrere Admins haben
4. der Admin einer Organisation hat folgende Featres:
    - [ ] Email-Bestätigung: Admin einer Organisation bekommt Mail wen sich ein neuer User in seiner Organisation registriert
        - [ ] Admin einer Organisation kann Users seiner Organisation freischalten über den link in der mail
    - [ ] der Admin der Organisation kann den Namen der eigenen Organisation bearbeiten
5. Editor kann nur eigene Organisations-Daten bearbeiten (filter implementieren für Kinder, Schedules, Waitlist)
  - Permission-Check:
    - Viewer/Editor: Nur eigene Schedules
    - Admin: Alle Schedules
6. 

## Checkliste: Admin-Features

- [ ] Tests für Admin-Funktionen

---

## Zusätzliche Features (Nice-to-have)

**Organisation Stats Dashboard:**
- Grafik: User pro Organisation
- Grafik: Kinder pro Organisation
- Grafik: Aktivität (Schedules erstellt pro Monat)

**Bulk Actions:**
- Mehrere Organisationen gleichzeitig deaktivieren
- Merge von Organisationen (bei Duplikaten)

**Email Notifications:**
- Admin bekommt Email bei neuer Organisation-Registrierung
- Admin bekommt Email bei neuem User in bestehender Org

mach du und löse alle probleme online, checke am ende mit einem playwright test, ob man sich online registrieren kann als neuere dmin mit test-daten, und einen scedule anlegen und kinder und waitlist und report. alles in dem lokalen playwright test von hier aus und erst aufhren wenn alles geht