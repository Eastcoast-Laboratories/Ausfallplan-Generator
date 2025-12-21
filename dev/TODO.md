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

4. der Admin einer Organisation hat folgende Featres:
    - [ ] Email-Bestätigung: Admin einer Organisation bekommt Mail wen sich ein neuer User in seiner Organisation registriert
        - [ ] Admin einer Organisation kann Users seiner Organisation freischalten über den link in der mail
    - [ ] der Admin der Organisation kann den Namen der eigenen Organisation bearbeiten
    

----

- Find firstOnWaitlist unit (not in this day)
 - Strategy: 
   1. First try queue (children that were skipped because they were in day)
   2. If queue empty, use current index from waitlist
   3. If child is in day, add to queue for next day and try next child
   4. Only increment index when we use a child from the main list (not from queue) or if we put the child in the queue

- datenschutzerklärung und Impressum verbessern

3.
Wenn noch keine Kinder in der Nachrückliste, dann alle in der selben Reihenfolge wie im schedule aufnehmen. (wahrscheinlich feritg)

4.
Neue Kinder immer gleich in den aktiven schedule und im die Nachrückliste aufnehmen (wahrscheinlich feritg)


2.
unter https://fairnestplan.z11.de/profile muss ein punkt sein, lösche mein konto

3.
wenn man noch keinen ausfallplan erstellt hat, dann darf http://localhost:8080/waitlist kein error anzeigen sondern umleiten zu schedules

4.
ein editor muss immer zugang zum Punkt Organisatinen haben und darf auch http://localhost:8080/admin/organizations/add aufrufen
um eine neue orga zu erstellen. und die zu löschen wo er roga-admin ist


# weitere TODOs

- erstelle in jedem vorhandenen playwright test im head eine Beschreibung, was der test genau tut, wenn der noch nicht da ist. wenn sich dabei herausstellt , dass der test etwas testet, was die neue organisation betrifft, dann aktualisiere diese tests bis sie erfolgreich durchlaufen. ergänze auch in jedem test die git commit id und die commit message als einzeiler, wobei der erstellt wurde höre nicht auf, bevor nicht alle spec.js aktualisiert sind im repository

## Checkliste: Admin-Features

- [ ] Tests für Admin-Funktionen

---

**Email Notifications:**
- Admin bekommt Email bei neuer Organisation-Registrierung
- Admin bekommt Email bei neuem User in bestehender Organiston
- wenn emails verschickt werden, auch immer eine kopie an ausfallplan-sysadmin@it.z11.de schicken. jede confirmation email und jede mail an organisations-admins, die user freischalten sollen, also ALLE emails als kopie an ausfallplan-sysadmin@it.z11.de schicken

-----

mach du und löse alle probleme online, checke am ende mit einem playwright test, ob man sich online registrieren kann als neuere dmin mit test-daten, und einen scedule anlegen und kinder und waitlist und report. alles in dem lokalen playwright test von hier aus und erst aufhren wenn alles geht





## Zusätzliche Features (Nice-to-have)

**Organisation Stats Dashboard:**
- Grafik: User pro Organisation
- Grafik: Kinder pro Organisation
- Grafik: Aktivität (Schedules erstellt pro Monat)

**Bulk Actions:**
- Merge von Organisationen (bei Duplikaten)

# aktuell:  
