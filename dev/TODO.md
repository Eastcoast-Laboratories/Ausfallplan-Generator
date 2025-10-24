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
6. erstelle eine neue join tabelle, die die user den organisationen zuordnen kann un dworin die rollle in der organisation definierrt wird, dazu müssen auch alle anzeigen aktualisiert werden, organisation index, view und edit, beiedit auch der bereich benutzer verwalten 

---

## 🔧 Implementationsplan: Organization-User Join Table (Many-to-Many)

### Ziel
User können Mitglied in mehreren Organisationen sein, mit unterschiedlichen Rollen pro Organisation.

### Aktuelle Struktur (1:n)
```
users
├── id
├── organization_id (FK)  ← WIRD OPTIONAL oder PRIMARY ORG
├── role                  ← WIRD ENTFERNT oder GLOBAL ROLE
└── ...

schedules
├── id
├── user_id              ← BLEIBT (erstellt von)
├── organization_id      ← BLEIBT (gehört zu org)
└── ...
```

### Neue Struktur (n:m)
```
organization_users (JOIN TABLE)
├── id (PK)
├── organization_id (FK → organizations.id)
├── user_id (FK → users.id)
├── role (enum: 'org_admin', 'editor', 'viewer')
├── is_primary (boolean) - Hauptorganisation
├── joined_at (datetime)
├── invited_by (FK → users.id, nullable)
└── UNIQUE(organization_id, user_id)

users
├── id
├── email
├── password
├── is_system_admin (boolean) - System-weite Admin-Rechte
└── ...
```

### Rollen-Konzept NEU
1. **System-Admin** (`is_system_admin = true`)
   - Zugriff auf ALLE Organisationen
   - Kann Organisationen erstellen/löschen
   - Kann beliebige User zu Orgs hinzufügen
   
2. **Org-Admin** (`organization_users.role = 'org_admin'`)
   - Volle Rechte in SEINER Organisation
   - Kann User seiner Org freischalten
   - Kann Org-Namen bearbeiten
   - Kann andere User zu Org einladen
   
3. **Editor** (`organization_users.role = 'editor'`)
   - Kann Daten seiner Org bearbeiten
   - Kann Schedules/Kinder/Waitlist verwalten
   
4. **Viewer** (`organization_users.role = 'viewer'`)
   - Nur Lese-Rechte

### Schedules: Brauchen wir eine Join-Tabelle?
**NEIN!** Schedules bleiben wie sie sind:
- `schedules.user_id` = Wer hat erstellt (Creator)
- `schedules.organization_id` = Zu welcher Org gehört es
- Zugriff wird über `organization_users` geregelt, NICHT über separate schedule_users

**Begründung:**
- Ein Schedule gehört zu EINER Organisation
- ALLE Mitglieder der Organisation haben Zugriff (je nach Rolle)
- Keine Notwendigkeit für granulare Per-Schedule-Permissions
- Einfacher und übersichtlicher

### Migrations-Plan

#### Phase 1: Neue Tabelle erstellen
```bash
bin/cake bake migration CreateOrganizationUsersTable
```

**Migration Inhalt:**
```php
CREATE TABLE organization_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'viewer',
    is_primary BOOLEAN DEFAULT FALSE,
    joined_at DATETIME NOT NULL,
    invited_by INT NULL,
    created DATETIME,
    modified DATETIME,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_org_user (organization_id, user_id)
);
```

#### Phase 2: Daten migrieren
```php
// In derselben Migration:
// Kopiere bestehende users.organization_id + users.role → organization_users
INSERT INTO organization_users (organization_id, user_id, role, is_primary, joined_at)
SELECT 
    organization_id,
    id as user_id,
    CASE 
        WHEN role = 'admin' THEN 'org_admin'
        WHEN role = 'editor' THEN 'editor'
        ELSE 'viewer'
    END as role,
    TRUE as is_primary,
    created as joined_at
FROM users
WHERE organization_id IS NOT NULL;
```

#### Phase 3: Users Tabelle anpassen
```php
// Neue Migration: ModifyUsersTable
ALTER TABLE users 
ADD COLUMN is_system_admin BOOLEAN DEFAULT FALSE;

// System-Admins markieren (die mit role='admin')
UPDATE users SET is_system_admin = TRUE WHERE role = 'admin';

// ENTFERNEN von role und organization_id (Clean Design!)
// Alle Authorization-Daten sind jetzt in organization_users
ALTER TABLE users DROP COLUMN role;
ALTER TABLE users DROP COLUMN organization_id;

// WICHTIG: Foreign Keys in anderen Tabellen bleiben!
// schedules.user_id → users.id (Creator)
// schedules.organization_id → organizations.id (Ownership)
```

**Warum entfernen?**
- ✅ Single Source of Truth: Nur organization_users definiert Zugehörigkeit
- ✅ Keine Redundanz/Inkonsistenzen
- ✅ Saubere Trennung: users = Auth, organization_users = Authorization
- ✅ Flexibel für Multiple Orgs
- ⚠️ Erfordert: Identity muss organization_users vorladen

### Model/Entity Änderungen

#### 1. Neue OrganizationUser Entity
```php
// src/Model/Entity/OrganizationUser.php
protected array $_accessible = [
    'organization_id' => true,
    'user_id' => true,
    'role' => true,
    'is_primary' => true,
    'joined_at' => true,
    'invited_by' => true,
];
```

#### 2. Neue OrganizationUsersTable
```php
// src/Model/Table/OrganizationUsersTable.php
$this->belongsTo('Organizations');
$this->belongsTo('Users');
$this->belongsTo('Inviters', [
    'className' => 'Users',
    'foreignKey' => 'invited_by'
]);
```

#### 3. Update UsersTable
```php
$this->belongsToMany('Organizations', [
    'through' => 'OrganizationUsers',
    'foreignKey' => 'user_id',
    'targetForeignKey' => 'organization_id'
]);
```

#### 4. Update OrganizationsTable
```php
$this->belongsToMany('Users', [
    'through' => 'OrganizationUsers',
    'foreignKey' => 'organization_id',
    'targetForeignKey' => 'user_id'
]);
```

#### 5. Update User Entity
```php
// Add field
protected array $_accessible = [
    'is_system_admin' => true,
    'organizations' => true,
    'organization_users' => true,
    // ... rest
];
```

### Controller Änderungen

#### AppController.php - Authorization Helper
```php
/**
 * Check if user has role in organization
 */
protected function hasOrgRole($organizationId, $role = null): bool
{
    $user = $this->Authentication->getIdentity();
    
    if ($user->is_system_admin) {
        return true;
    }
    
    $orgUser = $this->fetchTable('OrganizationUsers')->find()
        ->where([
            'user_id' => $user->id,
            'organization_id' => $organizationId
        ])
        ->first();
    
    if (!$orgUser) {
        return false;
    }
    
    if ($role === null) {
        return true; // Just check membership
    }
    
    // Role hierarchy: org_admin > editor > viewer
    $hierarchy = ['viewer' => 1, 'editor' => 2, 'org_admin' => 3];
    return $hierarchy[$orgUser->role] >= $hierarchy[$role];
}

/**
 * Get user's organizations
 */
protected function getUserOrganizations(): array
{
    $user = $this->Authentication->getIdentity();
    
    if ($user->is_system_admin) {
        return $this->fetchTable('Organizations')->find()->all()->toArray();
    }
    
    return $this->fetchTable('OrganizationUsers')
        ->find()
        ->where(['user_id' => $user->id])
        ->contain(['Organizations'])
        ->all()
        ->extract('organization')
        ->toArray();
}
```

#### UsersController.php - Registration
```php
// Bei Registration: Eintrag in organization_users erstellen
$orgUsersTable = $this->fetchTable('OrganizationUsers');
$orgUser = $orgUsersTable->newEntity([
    'organization_id' => $organization->id,
    'user_id' => $user->id,
    'role' => 'org_admin', // Erster User wird org_admin
    'is_primary' => true,
    'joined_at' => new DateTime(),
]);
$orgUsersTable->save($orgUser);
```

#### SchedulesController.php - Permission Check Update
```php
// Statt: $schedule->organization_id !== $user->organization_id
// Neu:
if (!$this->hasOrgRole($schedule->organization_id, 'editor')) {
    $this->Flash->error(__('Zugriff verweigert.'));
    return $this->redirect(['action' => 'index']);
}
```

#### OrganizationsController.php - User Management
```php
public function addUser($id)
{
    $organization = $this->Organizations->get($id);
    
    // Check permission
    if (!$this->hasOrgRole($id, 'org_admin')) {
        $this->Flash->error(__('Keine Berechtigung.'));
        return $this->redirect(['action' => 'index']);
    }
    
    if ($this->request->is('post')) {
        $data = $this->request->getData();
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        
        $orgUser = $orgUsersTable->newEntity([
            'organization_id' => $id,
            'user_id' => $data['user_id'],
            'role' => $data['role'] ?? 'viewer',
            'is_primary' => false,
            'joined_at' => new DateTime(),
            'invited_by' => $this->Authentication->getIdentity()->id,
        ]);
        
        if ($orgUsersTable->save($orgUser)) {
            $this->Flash->success(__('Benutzer hinzugefügt.'));
        }
    }
}

public function removeUser($orgId, $userId)
{
    // Check if user is org_admin
    if (!$this->hasOrgRole($orgId, 'org_admin')) {
        $this->Flash->error(__('Keine Berechtigung.'));
        return $this->redirect(['action' => 'index']);
    }
    
    // Check if user is last org_admin
    $orgUsersTable = $this->fetchTable('OrganizationUsers');
    $adminCount = $orgUsersTable->find()
        ->where([
            'organization_id' => $orgId,
            'role' => 'org_admin'
        ])
        ->count();
    
    $targetOrgUser = $orgUsersTable->find()
        ->where([
            'organization_id' => $orgId,
            'user_id' => $userId
        ])
        ->first();
    
    if ($adminCount === 1 && $targetOrgUser->role === 'org_admin') {
        $this->Flash->error(__('Letzter Admin kann nicht entfernt werden.'));
        return $this->redirect(['action' => 'view', $orgId]);
    }
    
    if ($orgUsersTable->delete($targetOrgUser)) {
        $this->Flash->success(__('Benutzer entfernt.'));
    }
}
```

### Template Änderungen

#### templates/Admin/Organizations/view.php
```php
<h4><?= __('Mitglieder') ?> (<?= count($organization->organization_users) ?>)</h4>

<table>
    <thead>
        <tr>
            <th><?= __('Benutzer') ?></th>
            <th><?= __('Rolle') ?></th>
            <th><?= __('Beigetreten') ?></th>
            <th><?= __('Hauptorganisation') ?></th>
            <th><?= __('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($organization->organization_users as $orgUser): ?>
        <tr>
            <td><?= h($orgUser->user->email) ?></td>
            <td>
                <?= $this->Form->postLink(
                    h($orgUser->role),
                    ['action' => 'changeUserRole', $organization->id, $orgUser->user_id],
                    ['class' => 'badge badge-' . $orgUser->role]
                ) ?>
            </td>
            <td><?= $orgUser->joined_at->format('d.m.Y') ?></td>
            <td><?= $orgUser->is_primary ? '⭐' : '' ?></td>
            <td>
                <?= $this->Form->postLink(
                    __('Entfernen'),
                    ['action' => 'removeUser', $organization->id, $orgUser->user_id],
                    ['confirm' => __('Wirklich entfernen?')]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

#### templates/Admin/Organizations/edit.php
```php
<!-- Benutzer hinzufügen Section -->
<h5><?= __('Benutzer zur Organisation hinzufügen') ?></h5>
<?= $this->Form->create(null, ['url' => ['action' => 'addUser', $organization->id]]) ?>
    <?= $this->Form->control('user_id', [
        'options' => $availableUsers,
        'label' => __('Benutzer auswählen')
    ]) ?>
    <?= $this->Form->control('role', [
        'options' => [
            'org_admin' => __('Organisations-Admin'),
            'editor' => __('Bearbeiter'),
            'viewer' => __('Betrachter')
        ],
        'label' => __('Rolle')
    ]) ?>
    <?= $this->Form->button(__('Hinzufügen')) ?>
<?= $this->Form->end() ?>
```

### Authentication Änderungen

#### Authentication/Identity anpassen

**WICHTIG: Identity muss organization_users vorladen!**

```php
// config/authentication.php oder AppController
'identityClass' => function($user) {
    // Lade organization_users beim Login
    $user->organization_users = TableRegistry::getTableLocator()
        ->get('OrganizationUsers')
        ->find()
        ->where(['user_id' => $user->id])
        ->contain(['Organizations'])
        ->all()
        ->toArray();
    
    return $user;
}

// In AppController oder Authentication setup
$user = $this->Authentication->getIdentity();

// Add helper methods to Identity
$user->isSystemAdmin() // statt $user->role === 'admin'
$user->hasOrgRole($orgId, 'editor')
$user->getOrganizations()
$user->getPrimaryOrganization()

// Beispiel Implementation in User Entity:
public function hasOrgRole($orgId, $requiredRole = null): bool
{
    if ($this->is_system_admin) {
        return true;
    }
    
    $orgUser = collection($this->organization_users)
        ->firstMatch(['organization_id' => $orgId]);
    
    if (!$orgUser) {
        return false;
    }
    
    if ($requiredRole === null) {
        return true; // Just membership check
    }
    
    $hierarchy = ['viewer' => 1, 'editor' => 2, 'org_admin' => 3];
    return $hierarchy[$orgUser->role] >= $hierarchy[$requiredRole];
}
```

### Validierung & Regeln

#### OrganizationUsersTable Rules
```php
public function buildRules(RulesChecker $rules): RulesChecker
{
    // Jede Organisation muss mindestens einen org_admin haben
    $rules->addDelete(function ($entity, $options) {
        $adminCount = $this->find()
            ->where([
                'organization_id' => $entity->organization_id,
                'role' => 'org_admin'
            ])
            ->count();
        
        if ($adminCount === 1 && $entity->role === 'org_admin') {
            return 'Letzte Organisation-Admin kann nicht entfernt werden.';
        }
        
        return true;
    }, 'lastAdminCheck');
    
    return $rules;
}
```

#### UsersTable Rules (NEU)
```php
public function buildRules(RulesChecker $rules): RulesChecker
{
    // User muss entweder System-Admin ODER in mindestens 1 Org sein
    $rules->addDelete(function ($entity, $options) {
        if (!$entity->is_system_admin) {
            $orgCount = $this->fetchTable('OrganizationUsers')
                ->find()
                ->where(['user_id' => $entity->id])
                ->count();
            
            if ($orgCount === 0) {
                return 'User muss entweder System-Admin sein oder mindestens einer Organisation angehören.';
            }
        }
        
        return true;
    }, 'userMustHaveOrgOrBeAdmin');
    
    return $rules;
}
```

### Testing

#### Unit Tests
- [ ] OrganizationUsersTable CRUD
- [ ] User kann mehreren Orgs beitreten
- [ ] Rollen-Hierarchie funktioniert
- [ ] Letzte Admin kann nicht gelöscht werden
- [ ] System-Admin hat Zugriff auf alles

#### Integration Tests
- [ ] Registration erstellt organization_user Eintrag
- [ ] Permission Checks mit neuer Struktur
- [ ] User zu Org hinzufügen/entfernen

#### E2E Tests (Playwright)
- [ ] Org-Admin kann User einladen
- [ ] Org-Admin kann Rollen ändern
- [ ] Editor kann keine User verwalten
- [ ] Viewer kann nichts bearbeiten
- [ ] User in mehreren Orgs kann wechseln

### Deployment Strategie

1. **Phase 1: Additive Changes** (kein Breaking)
   - Neue organization_users Tabelle
   - Daten migrieren
   - Neue Helper-Methoden
   - Alte Struktur PARALLEL laufen lassen

2. **Phase 2: Update Code** 
   - Controller auf neue Struktur umstellen
   - Templates aktualisieren
   - Tests ausführen

3. **Phase 3: Cleanup** (Breaking)
   - users.role entfernen
   - users.organization_id optional machen
   - Alte Permission-Checks entfernen

### Migration Rollback Plan
Falls etwas schiefgeht:
```sql
-- organization_users Tabelle droppen
DROP TABLE organization_users;

-- users.role wieder hinzufügen
ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'viewer';
UPDATE users SET role = 'admin' WHERE is_system_admin = TRUE;
```

### Checkliste Implementation
- [ ] Migration: CreateOrganizationUsersTable
- [ ] Migration: MigrateExistingUserOrgData  
- [ ] Migration: ModifyUsersTable (is_system_admin)
- [ ] Entity: OrganizationUser
- [ ] Table: OrganizationUsersTable + Associations
- [ ] Update: UsersTable associations
- [ ] Update: OrganizationsTable associations
- [ ] Helper: hasOrgRole(), getUserOrganizations()
- [ ] Controller: OrganizationsController (addUser, removeUser, changeRole)
- [ ] Controller: Update all permission checks
- [ ] Template: Organizations/view.php
- [ ] Template: Organizations/edit.php
- [ ] Template: Organizations/index.php
- [ ] Tests: Unit tests
- [ ] Tests: Integration tests
- [ ] Tests: E2E Playwright tests
- [ ] Deploy Phase 1
- [ ] Deploy Phase 2
- [ ] Deploy Phase 3 (Cleanup)

---

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

