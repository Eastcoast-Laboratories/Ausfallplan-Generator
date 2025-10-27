# TODO

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

6. erstelle eine neue join tabelle, die die user den organisationen zuordnen kann un dworin die rollle in der organisation definierrt wird, dazu müssen auch alle anzeigen aktualisiert werden, organisation index, view und edit, beiedit auch der bereich benutzer verwalten  @USER_RIGHTS.md#L1-581 

----


- @production-e2e.spec.ts#L211-212 wann kommt diese meldung? abhängig von erfolg der tests? oder immer, auch wenn die tests teileweise fehlschlagen?

bei http://localhost:8080/sibling-groups/view/5  bei jedem Kind einnen "Kind löschen" link mit confirm

- alle externen scripts, wie z.b. https://cdn.jsdelivr.net lokal vorhalten

- datenschutzerklärung und Impressum generieren und in der navigation ganz unten verlinken: Eastcoast Laboratories, Ruben Barkow-Kuder, Knickweg 16, 24114 Kiel, Telefon: Kiel-5368723, Kontakt: ausfallplan-generator-kontakt@it.z11.de

- npx playwright test test_import_gender_birthdate.spec.js --project=chromium 2>&1 | tail -50


bei http://localhost:8080/schedules/add muss eine selectbox, die nur sichtbar ist, wenn man mehreren organisationen angehört oder sysadmin ist um die organisation auszuwählen für die der neue plan sein soll

- registrierung:
 - beim eingeben der organisation soll das ayax weg, stattdessen eine selectbox mit der auswahl der organisationen die existieren bei denen man sich anmelden kann und als errste option in der selectbox: "neu organisation anlegen". wenn man eine existierende organisation auswählt, dann wird die selectbox unten mit den rollen "Viewer" und "Editor" und "Organization adminn" angezeigt
 - wenn man bei der registrierung eine neue organisation errstellt, dann müssen die anderen optionen "Viewer" und "Editor" in der selectbox untenversteckt werden und automatisch  "Orga-admin" ausgewählt werden
 - "Viewer" und "Editor" und "Organization adminn" und "Requested Role in Organization" muss noch auf deutsch
 - die sprachauswahl oben rechts muss auch im register form funktionieren, (im moment ist immer deutsch)
- phpunittests alle noch mal reparieren @phpunit_remaining_work.md#L1-312 

- [ ] die sprachwechslung von englisch zurück zu deutsch funktioniert nicht immer, mache einen playwright test, der sich einloggt, dann die sprache ein paar mal wechselt und die navigation überprüft ob das in der richtigen sprache ist TEST HINZUGEFÜGT in tests/e2e/language-switcher.spec.js aber noch nciht ausgeführt

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

