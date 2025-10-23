# TODO

## Current Sprint

- [x] Admin kann alle Schedules/Ausfallpläne sehen (mit User/Org-Spalten) - ✅ 23.10. 22:50
- [x] Admin kann Organisationen verwalten - ✅ 23.10. 23:00
- [x] Playwright Test: Admin login und Berechtigungen testen - ✅ 23.10. 23:00

## Backlog

- [ ] die navigation links einbauen mit wenn kleiner als 600px als hamburger umschalten
- [ ] eine möglichkeit seine email und sein passwort zu ändern
- [ ] Password recovery mit einem Konfirmationscode


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