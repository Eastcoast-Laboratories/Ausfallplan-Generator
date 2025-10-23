# TODO

- [ ] vervollständige die app
- [ ] führe das gesamte concept aus. mit unittests und localization: dev/CONCEPT.md
- [ ] im endeffekt will ich so einen ausfallplan generieren in der app: dev/Kindergarten-Ausfallplan_melsdorfer Str. ab 2025-10.png
- [ ] höre erst auf, wenn alle unittests durchlaufen. an entscheindenden stellen committe den stand, aber immer nur , wenn alle unittests durchlaufen
- [ ] die navigation muss einen logout button oben rechts anzeigen, wenn man eingeloggt ist und den usernamen in einem kreis
- [ ] eine möglichkeit seine email und sein passwort zu öndern
- [ ] die navigation links einbauen mit wenn kleiner als 600px als hamburger umschalten
- [x] Dienstplan (Schedule) umbenennen in Ausfallplan, (entsprechend in englisch) - ✅ 22.10. 18:55
- [ ] im Ausfallplan wieviele zählkinder maximal pro tag dürfen wird noch nciht korrekt gespeichert. baue einen unittest, der die anzahl der zählkinder speichert und prüft ob sie beim erneuten editieren angezeigt werden
- [ ] die nachrücklisten müssen einem Ausfallplan zugeordnet werden
- [ ] man darf in einer nachrückliste nur kinder auswählen, die in dem zugehörigen Ausfallplan liegen
- [ ] man muss pro scedule noch festlegen können, wieviele tage man im Report generiert haben will, vorgeschlagen wird dabei die anzahl dem scedule zugeordneter  zählkinder (z.b. 18) 
- [ ] bei scedule edit muss man analog zu der warteliste auch kinder zuordnen können mit einer join verknüpfung
- im ausfallplan muss man noch angeben können, über wieviele tage der plan gehen soll, als vorschlag wird die anzahl zählkinder angeboten, die dem plan zugeordnet sind

- Berichte kann entfernt werden aus der navígation, stattdessen muss in jedem ausflallplan in der zeile ein Buton "Ausfallplan generieren" eingebaut werden, der dann den plan erstellt, in der artwie dev/Kindergarten-Ausfallplan_melsdorfer Str. ab 2025-10.png

- Nächster Schritt:
Die eigentliche PDF/PNG-Generierung wie im Beispiel (dev/Kindergarten-Ausfallplan_melsdorfer Str. ab 2025-10.png) muss noch implementiert werden:

- Tages-Boxen mit Tiernamen
- Kinder-Listen pro Tag
- Nachrückliste rechts
- "Immer am Ende" Sektion

- die registration seite ist noch englisch, und "Please enter your email and password to access your account."im login screen, dort auch "Create new account"

"New Child" buttton auf http://localhost:8080/children/add da alles noch englisch

auf http://localhost:8080/children ist "Sibling group" noch enlisch und auch noch nicht gefüllt, die spalte


merke dir: playwright muss ja nicht  in dem docker laufen sondern lokal im host.

- Sorge dafür, dass die übersetzungsdateien in dem dochḱer mit gemountet sind, falls sie das noch nciht sind, (also nicht kopieren in den docker beim docker build)

- http://localhost:8080/schedules/manage-children/1 soll einen button add child bekommen#

- füge überall autofocus in dem sinnvollsten input feld ein, bei jedem view


- verändere die cake einstellungen so, dass er keinen cache mehr benutztt oder besser, wenn das geht, dass der cache nur 10s gültig ist

$ ssh eclabs-vm06 ls /var/kunden/webs/ruben/www/ausfallplan-generator.z11.de -la
insgesamt 8
drwxr-xr-x 2 root root 4096 22. Okt 06:35 .
drwxr-xr-x 9 root root 4096 22. Okt 06:34 ..
ruben@heisenberg:/var/www/Ausfallplan-Generator$ 

mache dort ein git clone rein aus git@github.com:Eastcoast-Laboratories/Ausfallplan-Generator.git und installiere alles dort, was nötig ist, also vervollständige die app dort

das mysql passwort dort ist i1aeLZFUmoo7mWdy für die db "ausfallplan_generator" speichere dies in einer .env datei, die in gitignore ist




## Fertiggestellt

### Authentication & Navigation (22.10. 08:37)
- ✅ Login/Logout mit clean URLs: `/login`, `/logout`
- ✅ User-Registration: `/register`
- ✅ Profile-Seite: `/profile` (Email & Passwort ändern)
- ✅ Responsive Navigation mit Hamburger-Menü (<600px)
- ✅ User-Avatar im Kreis mit Dropdown-Menü
- ✅ Language Switcher (DE/EN)
- ✅ 37 Tests, 100 Assertions - alle bestanden ✅

### Business Logic Services (22.10. 08:19)
- ✅ WaitlistService mit Priority-basierter Sortierung
- ✅ Geschwistergruppen-Support (atomar)
- ✅ Integrative Gewichtung (×2)
- ✅ 7 Service-Tests - alle bestanden ✅