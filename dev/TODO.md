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


auf http://localhost:8080/children ist "Sibling group" noch enlisch und auch noch nicht gefüllt, die spalte


- Sorge dafür, dass die übersetzungsdateien in dem docker mit gemountet sind, falls sie das noch nciht sind, (also nicht kopieren in den docker beim docker build)

- in der waitlist der button zum hinzufügen aller kinder geht noch nciht, er sagt immer "All children are already on the waitlist." . mache einen playwright test, der das testet: er soll einen neuen schedule anlegen, zwei kinder erstellen, diese dann mit dem button alle hinzufügen

- report: neben der nachrückliste zwei weitere kontrollspalten in grau:
 - eine zeigt an, wie oft ein kind in allen tagen gelistet ist
 - eine zeigt an, wie oft ein kind unten als erstes kind der nachrückliste vorkommt

- bei [add child](http://localhost:8080/children/index) soll der erste tab zum button "Add Child" führen, der soll auch den autofocus haben

- "Remove from schedule" confirm soll weg, auch die translation s löschen


- im report die verteilung der kinder: alle kinder im round-robin eingetragen werden wobei ein integratives kind übersprungen wird, wenn die maximale pro tag nur noch einen platz übrig hat, dann muss noch sicherstellen, dass ein kind das übersprungen wurde, dass dieses kind dann am nächsten tag eingefügt wird, damit alle kinder im endeffekt gleich oft vorkommen. unter jedem tag soll ein kind stehen, das als erstes aus der nachrückliste kommt, wobei aus der nachrückliste in der reihenfolge durchgegangen wird und hier keins stehen darf, das an dem tag schon drin ist



Wenn man bei register in organisation eine aus denen schon vorhandenen autovervollständigen vorschlagen und wenn es keine autovervollständigung gibt, dann soll der Text eine andere Farbe kriegen und daneben ein Hinweis neue Organisation erstellen. Tu es allé? Organisationen. Wenn sich jemand anmeldet bei einer schon vorhandenen Organisation, dann muss er eine Ok Google tot, um seine E-Mail zu bestätigen und der Admin der Organisation muss auch eine Mail bekommen, um den User freizuschalten. Für seine Organisation. Der Admin von einer Organisation muss einen Element finden. Deaktivieren. 

Password recoveryan mit einem konfirmationscode. 

Wenn jemand nur Mitglied einer Organisation ist, dann darf er nur die datenorganisation sehen, aber nicht editieren 

Ein admin Konto kann alles sehen und alles editieren 

Ein Bearbeiter Konto kann nur die Kinder, die seine Organisation zu gehören, editieren und hinzufügen und löschen. Das heißt im Kinder tabelle muss ein Feld für die zugehörige Organisation mit rein