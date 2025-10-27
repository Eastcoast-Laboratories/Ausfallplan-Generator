## 📦 Neues Feld: `display_name` in Children

### Konzept

**Statt** komplizierte Logik im Report → **Einfach**: `display_name` Feld in `children` Tabelle

### Database Schema

```sql
ALTER TABLE children 
ADD COLUMN display_name VARCHAR(100) NULL 
COMMENT 'Pre-formatted name for display in reports based on anonymization choice';
```

### Wann wird display_name gefüllt?

**1. Beim CSV-Import:**
```php
// Import Preview: User wählt Anonymisierungs-Modus
$anonymizationMode = $_POST['anonymization_mode']; // 'full_name', 'first_name', 'animal_name', etc.

// Import Confirm: display_name wird basierend auf Auswahl gefüllt
switch ($anonymizationMode) {
    case 'full_name':
        $displayName = $firstName . ' ' . $lastName;
        break;
    case 'first_name_only':
        $displayName = $firstName;
        break;
    case 'initial_last_name':
        $displayName = substr($firstName, 0, 1) . '. ' . $lastName;
        break;
    case 'animal_name':
        $displayName = $animalName;
        break;
    case 'initial_animal':
        $displayName = substr($firstName, 0, 1) . '. ' . $animalName;
        break;
}

$child = $this->Children->newEntity([
    'name' => $firstName,
    'last_name' => $lastName,
    'display_name' => $displayName,  // ✅ Pre-formatted
    // ... other fields
]);
```

**2. Beim manuellen Hinzufügen (Children/add):**
```php
// Default: Voller Name
$displayName = $data['name'] . ' ' . $data['last_name'];

// Oder: User kann wählen (optional)
$displayName = $data['display_name'] ?? ($data['name'] . ' ' . $data['last_name']);
```

**3. Beim Bearbeiten (Children/edit):**
```php
// User kann display_name überschreiben
// Oder automatisch neu generieren basierend auf Organisation-Einstellung
```

### Vorteile

✅ **Performance:** Keine Berechnung zur Laufzeit  
✅ **Flexibilität:** Jedes Kind kann eigenen Display-Name haben  
✅ **Einfachheit:** Report verwendet einfach `child.display_name`  
✅ **Override:** User kann manuell anpassen bei Bedarf  
✅ **Backward Compatible:** NULL → Fallback zu `name + last_name`  

### Report Integration

```php
// VORHER (kompliziert):
$displayName = $organization->formatChildNameForReport($child);

// NACHHER (einfach):
$displayName = $child->display_name ?? ($child->name . ' ' . ($child->last_name ?? ''));
```

### Migration für Bestehende Kinder

```sql
-- Fill display_name for existing children without it
UPDATE children 
SET display_name = CONCAT(name, ' ', COALESCE(last_name, ''))
WHERE display_name IS NULL;
```

### Child Entity Update

```php
// src/Model/Entity/Child.php

protected array $_accessible = [
    // ... existing fields ...
    'display_name' => true,
];

/**
 * Virtual field for backward compatibility
 */
protected function _getFullName(): string
{
    return $this->display_name ?? ($this->name . ' ' . ($this->last_name ?? ''));
}
```