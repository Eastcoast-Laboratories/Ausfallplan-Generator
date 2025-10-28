# Refactoring Plan: Umstellung auf Children-Felder

## Ziel
Umstellung von `waitlist_entries` Tabelle auf direkte Felder in `children` Tabelle.

## Neue Architektur

### Children-Felder
- `schedule_id` - Aktueller Schedule des Kindes
- `organization_order` - Sortierreihenfolge in Organisation
- `waitlist_order` - Sortierreihenfolge in Warteliste

### Alt → Neu Mapping

| Alt | Neu |
|-----|-----|
| `waitlist_entries.schedule_id` | `children.schedule_id` |
| `waitlist_entries.priority` | `children.waitlist_order` |
| `waitlist_entries.child_id` | Direkt `children.id` |

## Betroffene Dateien

### 1. Services
- ✅ `WaitlistService.php` - Komplett refactoren
- ✅ `ReportService.php` - Queries anpassen

### 2. Controller
- ✅ `WaitlistController.php` - Hauptlogik umbauen
- ✅ `SchedulesController.php` - Schedule-Zuweisungen
- ✅ `Admin/OrganizationsController.php` - Delete cascade

### 3. Models
- ✅ `ChildrenTable.php` - Neue Assoziationen
- ⚠️ `WaitlistEntriesTable.php` - Markieren für Deprecation

### 4. Templates
- ✅ `Waitlist/index.php` - UI anpassen
- ✅ `Schedules/generate_report.php` - Report anpassen

## Migration Strategie

### Phase 1: Neue Felder nutzen (JETZT)
1. WaitlistService umbauen
2. WaitlistController anpassen
3. ReportService anpassen
4. Tests fixen

### Phase 2: Cleanup (SPÄTER)
1. WaitlistEntries als deprecated markieren
2. Migration für Datenmigration (falls prod-Daten existieren)
3. WaitlistEntriesTable entfernen

## Logik-Änderungen

### Waitlist Query (Alt)
```php
$waitlist = $this->fetchTable('WaitlistEntries')
    ->find()
    ->contain(['Children'])
    ->where(['schedule_id' => $scheduleId])
    ->orderBy(['priority' => 'ASC']);
```

### Waitlist Query (Neu)
```php
$waitlist = $this->fetchTable('Children')
    ->find()
    ->where([
        'schedule_id' => $scheduleId,
        'waitlist_order IS NOT' => null
    ])
    ->orderBy(['waitlist_order' => 'ASC']);
```

### Kind zur Waitlist hinzufügen (Alt)
```php
$waitlistEntry = $waitlistTable->newEntity([
    'schedule_id' => $scheduleId,
    'child_id' => $childId,
    'priority' => $maxPriority + 1
]);
$waitlistTable->save($waitlistEntry);
```

### Kind zur Waitlist hinzufügen (Neu)
```php
$child = $childrenTable->get($childId);
$child->schedule_id = $scheduleId;
$child->waitlist_order = $maxWaitlistOrder + 1;
$childrenTable->save($child);
```

## Vorteile der neuen Architektur

1. ✅ **Einfacher:** Keine separate Tabelle mehr
2. ✅ **Performanter:** Weniger JOINs nötig
3. ✅ **Flexibler:** `organization_order` für andere Use Cases
4. ✅ **Konsistent:** Alles in `children` Tabelle
5. ✅ **Sauberer:** Foreign Keys direkt am Kind

## Risiken & Mitigation

### Risiko 1: Datenverlust
- **Mitigation:** Migration schreiben die alte Daten migriert
- **Status:** Aktuell keine Prod-Daten, daher unkritisch

### Risiko 2: Tests brechen
- **Mitigation:** Systematisch testen nach jedem Service
- **Status:** In Arbeit

### Risiko 3: Komplexe Queries
- **Mitigation:** Indexes auf den neuen Feldern
- **Status:** ✅ Indexes bereits erstellt

## Status

- [x] Felder in DB hinzugefügt
- [ ] WaitlistService refactored
- [ ] WaitlistController refactored
- [ ] ReportService refactored
- [ ] SchedulesController refactored
- [ ] Templates angepasst
- [ ] Tests grün
