# TODO

- [ ] vervollständige die app
- [ ] führe das gesamte concept aus. mit unittests und localization: dev/CONCEPT.md
- [ ] im endeffekt will ich so einen ausfallplan generieren in der app: dev/Kindergarten-Ausfallplan_melsdorfer Str. ab 2025-10.png
- [ ] höre erst auf, wenn alle unittests durchlaufen. an entscheindenden stellen committe den stand, aber immer nur , wenn alle unittests durchlaufen
- [ ] die navigation muss einen logout button oben rechts anzeigen, wenn man eingeloggt ist und den usernamen in einem kreis
- [ ] eine möglichkeit seine email und sein passwort zu öndern
- [ ] die navigation links einbauen mit wenn kleiner als 600px als hamburger umschalten

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