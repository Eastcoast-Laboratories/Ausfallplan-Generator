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

---

## Admin: Alle Ausfallpläne sehen

**Status:** TODO

**Anforderung:**
- Admin Account soll ALLE Ausfallpläne von ALLEN Usern sehen können
- Extra Spalte mit User-Name/Email hinzufügen
- Filterbar nach Organisation/User

**Implementierung:**
1. `SchedulesController`: Admin-Check einbauen
   - Wenn Admin: `find()->contain(['Users', 'Users.Organizations'])`
   - Wenn normal User: `find()->where(['user_id' => $currentUserId])`

2. `templates/Schedules/index.php`: 
   - Extra Spalte "User" hinzufügen (nur für Admin sichtbar)
   - Extra Spalte "Organisation" hinzufügen (nur für Admin sichtbar)
   - Filter-Dropdown für Organisation

3. Permission-Check:
   - Viewer/Editor: Nur eigene Schedules
   - Admin: Alle Schedules

**Files zu ändern:**
- `src/Controller/SchedulesController.php` (index action)
- `templates/Schedules/index.php`
- Keine Migration nötig (Relation existiert schon)

---

## Admin: Organisationen-Verwaltung

**Status:** TODO

**Anforderung:**
- Neuer Navi-Punkt "Organisationen" (nur für Admin)
- Liste aller Organisationen
- Anzeigen: Name, Anzahl User, Anzahl Kinder, Erstellt am
- Actions: Ansehen, Bearbeiten, Löschen (mit Warnung)

**Implementierung:**

### 1. Migration: Organizations Table erweitern
```bash
bin/cake bake migration AddAdminFieldsToOrganizations
```

**Felder hinzufügen:**
- `is_active` (boolean, default 1) - Organisation kann deaktiviert werden
- `settings` (text, nullable) - JSON für Org-spezifische Settings
- `contact_email` (string, nullable) - Kontakt-Email der Organisation
- `contact_phone` (string, nullable) - Telefonnummer

**Migration Code:**
```php
public function change()
{
    $table = $this->table('organizations');
    $table->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
          ->addColumn('settings', 'text', ['null' => true])
          ->addColumn('contact_email', 'string', ['limit' => 255, 'null' => true])
          ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
          ->update();
}
```

### 2. Controller erstellen
**File:** `src/Controller/Admin/OrganizationsController.php`

**Actions:**
- `index()` - Liste aller Organisationen mit Stats
- `view($id)` - Details einer Organisation (User, Kinder, Schedules)
- `edit($id)` - Organisation bearbeiten (Name, Kontakt, Settings)
- `delete($id)` - Organisation löschen (nur wenn keine User mehr!)
- `deactivate($id)` - Organisation deaktivieren

**Stats Query:**
```php
$orgsTable->find()
    ->select([
        'Organizations.*',
        'user_count' => $orgsTable->Users->find()->func()->count('*'),
        'children_count' => $orgsTable->Children->find()->func()->count('*'),
    ])
    ->leftJoinWith('Users')
    ->leftJoinWith('Children')
    ->group(['Organizations.id']);
```

### 3. Views erstellen

**File:** `templates/Admin/Organizations/index.php`
- Tabelle mit: Name, User Count, Children Count, Status, Actions
- Filter: Aktiv/Inaktiv, Suche nach Name
- Sortierbar nach Name, User Count, Created

**File:** `templates/Admin/Organizations/view.php`
- Organisation Details (Name, Kontakt, Created)
- Liste aller User dieser Org (mit Status, Rolle)
- Liste aller Kinder dieser Org
- Liste aller Schedules dieser Org
- Actions: Edit, Deactivate

**File:** `templates/Admin/Organizations/edit.php`
- Form: Name, Contact Email, Contact Phone
- Checkbox: is_active
- Settings (JSON Editor oder Key-Value Pairs)

### 4. Navigation erweitern

**File:** `templates/layout/authenticated.php`

Im Sidebar nur für Admin anzeigen:
```php
<?php if ($user && $user->role === 'admin'): ?>
    <a href="/admin/organizations" class="sidebar-nav-item">
        <span>🏢</span>
        <span><?= __('Organizations') ?></span>
    </a>
<?php endif; ?>
```

### 5. Routes hinzufügen

**File:** `config/routes.php`

```php
// Admin routes (only for admins)
$builder->prefix('Admin', function ($routes) {
    $routes->connect('/organizations', ['controller' => 'Organizations', 'action' => 'index']);
    $routes->connect('/organizations/view/{id}', ['controller' => 'Organizations', 'action' => 'view']);
    $routes->connect('/organizations/edit/{id}', ['controller' => 'Organizations', 'action' => 'edit']);
    $routes->connect('/organizations/delete/{id}', ['controller' => 'Organizations', 'action' => 'delete']);
    $routes->connect('/organizations/deactivate/{id}', ['controller' => 'Organizations', 'action' => 'deactivate']);
});
```

### 6. Permissions Check

**File:** `src/Middleware/AuthorizationMiddleware.php`

Admin-Prefix prüfen:
```php
$prefix = $request->getParam('prefix');
if ($prefix === 'Admin' && $role !== 'admin') {
    // Redirect with error
    return $response->withStatus(403);
}
```

### 7. Model Relations sicherstellen

**File:** `src/Model/Table/OrganizationsTable.php`

Relationen definieren:
```php
$this->hasMany('Users', [
    'foreignKey' => 'organization_id',
]);
$this->hasMany('Children', [
    'foreignKey' => 'organization_id',
]);
```

**File:** `src/Model/Table/UsersTable.php`
```php
$this->belongsTo('Organizations', [
    'foreignKey' => 'organization_id',
]);
```

---

## Checkliste: Admin-Features

- [ ] Migration: AddAdminFieldsToOrganizations erstellen und ausführen
- [ ] Admin/OrganizationsController erstellen
- [ ] Admin/Organizations Views erstellen (index, view, edit)
- [ ] Schedules: Admin sieht alle mit User/Org Spalte
- [ ] Navigation: "Organisationen" Link für Admin
- [ ] Routes für Admin-Prefix
- [ ] AuthorizationMiddleware: Admin-Prefix Check
- [ ] Model Relations in OrganizationsTable
- [ ] Tests für Admin-Funktionen
- [ ] Dokumentation schreiben

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