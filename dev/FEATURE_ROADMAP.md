# Feature Roadmap - Ausfallplan Generator

## ✅ Completed
- [x] Dashboard redirect funktioniert
- [x] Active schedule in session (Waitlist & Children)
- [x] Organization field bei Registration (optional)

## 📋 Planned Features

### 🔐 Authentication & Authorization

#### 1. Organization Management mit Autocomplete
- [ ] Bei Registration: Autocomplete für existierende Organisationen
- [ ] Wenn Organisation nicht existiert: Andere Textfarbe + Hinweis "Neue Organisation erstellen"
- [ ] Liste aller Organisationen für Autocomplete-Vorschläge

#### 2. Email-Bestätigung & Admin-Freischaltung
- [ ] **User registriert sich bei existierender Organisation:**
  - User bekommt Bestätigungs-Email
  - Admin der Organisation bekommt Email
  - Admin muss User freischalten
- [ ] **Confirmation-Code System:**
  - Email mit Code
  - User gibt Code ein zur Bestätigung
  - Admin-Freischaltung erforderlich

#### 3. Password Recovery
- [ ] "Passwort vergessen" Link
- [ ] Email mit Confirmation-Code
- [ ] Neues Passwort setzen mit Code

#### 4. User Management für Admins
- [ ] Admin kann User aktivieren/deaktivieren
- [ ] Liste aller User der eigenen Organisation
- [ ] User-Status ändern (aktiv/inaktiv)

### 👥 Permissions & Rollen

#### Role-Based Access Control (RBAC)

**Viewer** (Nur Lesen):
- [ ] Kann nur Daten der eigenen Organisation sehen
- [ ] Keine Edit-Rechte
- [ ] Nur Ansicht von: Schedules, Children, Waitlist, Reports

**Editor** (Bearbeiten):
- [ ] Kann Daten der eigenen Organisation editieren
- [ ] Kann Children hinzufügen/editieren/löschen
- [ ] Kann Schedules erstellen/bearbeiten
- [ ] Kann Waitlist verwalten
- [ ] **WICHTIG**: Children-Tabelle braucht `organization_id` Feld!

**Admin** (Alles):
- [ ] Kann alles sehen (alle Organisationen)
- [ ] Kann alles editieren
- [ ] User-Verwaltung
- [ ] Organisations-Verwaltung

### 🗂️ Database Changes

#### Children Table
- [ ] Migration: Add `organization_id` column to `children` table
- [ ] Foreign Key zu `organizations` table
- [ ] Index auf `organization_id`
- [ ] Update ChildrenController: Filter nach organization_id
- [ ] Update alle Queries mit organization_id filter

#### Email Confirmation
- [ ] Neue Tabelle: `user_confirmations`
  - user_id
  - confirmation_code
  - created_at
  - expires_at
  - confirmed_at
- [ ] Neue Tabelle: `password_resets`
  - user_id
  - reset_code
  - created_at
  - expires_at
  - used_at

#### User Status
- [ ] Add `status` column to `users` table
  - 'pending' (Email nicht bestätigt)
  - 'active' (Bestätigt & freigeschaltet)
  - 'inactive' (Deaktiviert)
- [ ] Add `confirmed_at` column (Email-Bestätigung)
- [ ] Add `approved_at` column (Admin-Freischaltung)
- [ ] Add `approved_by` column (Welcher Admin hat freigeschaltet)

### 📧 Email System

#### Setup Email Service
- [ ] Email-Config in `app.php`
- [ ] SMTP Settings
- [ ] Email-Templates erstellen:
  - Welcome Email (mit Confirmation Code)
  - Admin Notification (neuer User wartet)
  - Password Reset Email
  - User Approved Email

#### Email Queue
- [ ] Optional: Queue System für Emails
- [ ] Background Job Processing

### 🎨 UI/UX Improvements

#### Organization Autocomplete
- [ ] JavaScript Autocomplete Component
- [ ] API Endpoint: `/api/organizations/search?q=...`
- [ ] Styling: Unterschiedliche Farbe für neue Org
- [ ] Hover-Tooltip: "Diese Organisation existiert noch nicht"

#### User Management Interface
- [ ] Admin-Panel für User-Verwaltung
- [ ] Liste aller pending users
- [ ] Approve/Reject Buttons
- [ ] User aktivieren/deaktivieren Toggle

---

## 🚀 Implementation Priority

### Phase 1: Database & Permissions (Foundation)
1. Add `organization_id` to children table
2. Add `status`, `confirmed_at`, `approved_at` to users
3. Implement RBAC middleware
4. Update all queries mit organization_id filter

### Phase 2: Email System
1. Setup email configuration
2. Create email templates
3. Implement confirmation code system
4. Password reset functionality

### Phase 3: User Management
1. Admin user management interface
2. User approval workflow
3. User activation/deactivation

### Phase 4: Organization Autocomplete
1. API endpoint für organization search
2. Frontend autocomplete component
3. Visual feedback für neue/existierende Orgs

---

## 📝 Notes

- Children müssen einer Organisation zugeordnet sein
- Editor können nur Children ihrer eigenen Organisation sehen/editieren
- Admin können alle Children sehen/editieren
- Email-Bestätigung ist Pflicht für neue User
- Admin-Freischaltung ist Pflicht für neue User in existierenden Orgs
