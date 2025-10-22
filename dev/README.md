# Ausfallplan-Generator (CakePHP 5) — Project Blueprint

> **Purpose**  
> This single file is a complete blueprint for GitHub Copilot (and developers) to generate a full CakePHP 5 application that creates printable **Ausfallpläne** (absence/day plans) for childcare groups. It includes domain model, APIs, UX flows, security, i18n (de/en), tests, and acceptance criteria. Drop this file into an empty repo as `README.md`, push to GitHub, and start building—Copilot can infer files, classes, and tests from here.

---

## 0. Tech Stack & Non–functional Requirements

- **Language/Runtime:** PHP 8.4
- **Framework:** CakePHP 5.x (ORM, Authentication & Authorization plugins)
- **DB:** MySQL 8
- **Frontend:** Server-rendered CakePHP templates + HTMX/Alpine.js for interactivity
- **Build/Dev:** Composer, PHPStan (level 8), PHPUnit, Psalm (optional), Rector (optional)
- **Security:** HTTPS, strong password hashing (password_hash default), CSRF, rate limiting
- **PDF/PNG Export:** dompdf/dompdf or spatie/browsershot
- **i18n:** de_DE & en_US locales (UI + emails + PDF labels)
- **Container:** Optional Docker dev setup
- **License:** MIT

---

## 1. Product Summary

A multi-tenant web app for Kitas (organizations) to create and manage **Schedules** (a whole plan for a period), consisting of multiple **ScheduleDays** ("Ameisen-Tag 1", ...). Children are assigned fairly across days with capacity limits, **integrative** children count double, **sibling groups** are assigned atomically, and a **waitlist** fills empty slots with **priorities**. Each day supports a **start child** to rotate waitlist fairness. Admins export beautiful PDF/PNG posters.

### Core Features
- Organizations, users, roles (admin/editor/viewer)
- Children management, flags (integrative), sibling groups
- Schedules with multiple days, capacities, ordering
- Automatic distribution algorithm + manual drag & drop
- Global waitlist per schedule with **priority** and per-day **start child**
- Rules per schedule (JSON): `integrative_weight`, `always_last`, `max_per_child`
- PDF/PNG export styled as multi-card layout (like provided sample image)
- DE/EN localization for UI and exports
- Registration, email verification, password recovery
- Brute-force protection (rate limit & lockouts)
- Pricing plans & landing page; free **Test Plan**

---

## 2. Domain Model (ER overview)

```
Organization 1--* Users
Organization 1--* Children
Organization 1--* Schedules
Schedule 1--* ScheduleDays
Schedule 1--* Rules
Schedule 1--* WaitlistEntries
ScheduleDay 1--* Assignments
Child 0..1 -- 1 SiblingGroup  (aka Family)    // a group has many Children
```

### Entities & Fields

**organizations**
- id, name, locale(default), created, modified

**users**
- id, organization_id FK
- email (unique per org), password, role: `admin|editor|viewer`
- email_verified_at (nullable)
- last_login_at
- created, modified

**children**
- id, organization_id FK
- name (unique within org), is_integrative(bool), is_active(bool)
- sibling_group_id (nullable)
- created, modified

**sibling_groups** (aka families)
- id, organization_id, label (optional)
- created, modified

**schedules**
- id, organization_id FK
- title, starts_on(date), ends_on(date), state: `draft|final`
- created, modified

**schedule_days**
- id, schedule_id FK
- title (e.g., "Ameisen-Tag 1"), position(int), capacity(int, default 9)
- start_child_id (nullable, FK to children)  // rotates waitlist start per day
- created, modified

**assignments**
- id, schedule_day_id FK, child_id FK
- weight(int default 1)  // integrative children use schedule rule weight
- source: `auto|manual|waitlist`
- created, modified
- unique(schedule_day_id, child_id)

**waitlist_entries**
- id, schedule_id FK, child_id FK
- priority(int, higher = more important)
- remaining(int default 1) // number of times the child may be added from waitlist
- created, modified
- unique(schedule_id, child_id)

**rules**
- id, schedule_id FK
- key(varchar) e.g. `integrative_weight`, `always_last`, `max_per_child`
- value(json)

---

## 3. Business Logic (Algorithm Blueprint)

### 3.1 Automatic Distribution
- Build a candidate list of active children in the organization.
- Exclude `always_last` names initially; add them in a second pass.
- Respect **sibling groups**: a group is assigned **atomically** to a day. The sum of member weights counts against capacity. If the group doesn't fit, try the next day.
- Weight: `1` normal, `integrative_weight` (default `2`) for `is_integrative` children.

**Fairness approach:**
- Round-robin over days; for each step, attempt to place the next candidate (or sibling group) on the next day that has room and where the child/group is not yet assigned.
- Enforce `max_per_child` per schedule.
- After main loop, process `always_last` list with the same constraints.

### 3.2 Waitlist Filling
- Sort `waitlist_entries` by `priority DESC, created ASC`.
- For each **ScheduleDay**, set cursor to `start_child_id` if present, otherwise first in the sorted list. Iterate circularly (round-robin) to try filling gaps until capacity reached.
- Each time a child/group is added from the waitlist:
  - decrement its `remaining` (stop when zero)
  - update day's `start_child_id` to the **next** child in the round-robin sequence for fairness over time.

### 3.3 Manual Overrides
- Drag & drop UI must: validate capacity (including group weights), prevent partial sibling placement unless user explicitly opts into a "Split once" override, which is tracked on the assignment(s).

---

## 4. API & Pages (minimal contract)

### Public
- **Landing Page** `/` "” marketing, features, screenshots, pricing tiers, free Test Plan CTA.
- **Auth** `/register`, `/login`, `/logout`
- **Email verification** `/verify/:token`
- **Password reset** `/password/forgot`, `/password/reset/:token`

### App (authenticated)
- **Dashboard**: recent schedules, alerts, capacity warnings
- **Children**: list, create, edit, CSV import, sibling group assignment
- **Schedules**: CRUD
  - Detail view: grid of **ScheduleDays** (cards), live counters (used/total), badges for integrative (×2)
  - Buttons: *Auto-distribute*, *Apply Waitlist*, *Export PDF/PNG*, *Finalize*
  - Panel: **Waitlist** with priorities and remaining; **Rules** (JSON form)
  - Per day: **start child** picker, capacity, position drag
- **Exports**: `/schedules/:id/export.pdf`, `/schedules/:id/export.png`

### REST (example)
- `POST /api/schedules/:id/build` "” run auto distribution
- `POST /api/schedules/:id/apply-waitlist` "” fill from waitlist
- CRUD for children, schedules, schedule_days, waitlist_entries, rules

---

## 5. Security & Compliance

- Email verification before full access
- Rate limit & lockout:
  - after 5 failed logins: 15 min lockout
  - IP rate limit on auth endpoints
  - soft-CAPTCHA on anomalous patterns
- Password reset tokens: 60 min validity, one-time, invalidate old sessions
- Roles:
  - **admin**: all, billing
  - **editor**: manage children, schedules
  - **viewer**: read-only
- GDPR: imprint, privacy, data export/delete on request
- CSP, SameSite cookies, CSRF, HTTPS, secure headers

---

## 6. Localization (DE/EN)

- Use CakePHP i18n. Provide language switcher and org default locale.
- Resource files:
  - `resources/locales/de_DE/app.php` / `en_US/app.php`
- Translate: UI labels, emails, PDF headings, errors.
- Date/number formatting based on locale.

---

## 7. Pricing & Plans (for landing page)

- **Test Plan (Free):** 1 org, 1 schedule active, up to 25 children, PDF export, community support.
- **Pro:** Unlimited schedules, priority waitlist, CSV import, custom PDF themes.
- **Enterprise:** SSO/SAML, SLAs, audit logs, dedicated support.

Billing via Stripe (monthly).

---

## 8. Acceptance Criteria & Test Matrix

### 8.1 Unit Tests (examples)
- **RulesService**: returns defaults; overrides per schedule.
- **Weighting**: integrative child uses `integrative_weight` in sums.
- **SiblingGroup**: assignment is atomic; partial only with override flag.
- **Capacity**: sums of weights never exceed capacity.
- **MaxPerChild**: child not assigned beyond limit.
- **Waitlist**: priority ordering respected; `remaining` decremented; per-day `start_child_id` rotation works.

### 8.4 Integration Tests
- `POST /api/schedules/:id/build` results in expected number of assignments.
- Applying waitlist fills only remaining capacity and honors priorities & rotation.
- Manual drag & drop preserves invariants (no duplicates, capacity OK).

### 8.3 Feature/BDD (Gherkin samples)

```gherkin
Feature: Sibling groups are placed atomically
  Scenario: A day has capacity 9 and a 3"‘child family (weights 1,1,2)
    Given a schedule day with capacity 9
    And a sibling group of three children where one is integrative
    When I auto-distribute
    Then the family is placed together on a day
    And the used capacity is 4
```

```gherkin
Feature: Waitlist fairness with start child
  Scenario: Start rotates after a successful fill
    Given a sorted waitlist [A(priority 3), B(2), C(1)]
    And the schedule day start child is B
    When I apply the waitlist and only one slot is free
    Then B is assigned
    And the day's start child becomes C
```

---

## 9. Developer Tasks (Guided for Copilot)

Create files and implementations in this order:

1. **Bootstrap**
   - Composer `cakephp/app` skeleton, Docker (optional), `.env.example`
   - Install plugins: `cakephp/authentication`, `cakephp/authorization`, `dompdf/dompdf`
2. **Migrations & Seeds**
   - Implement tables described above + seed demo data
3. **Models & Associations**
   - All entities, validation rules, integrity rules, behaviors (Timestamp, CounterCache if needed)
4. **Services**
   - `ScheduleBuilder` (auto distribution + waitlist apply)
   - `WaitlistService`, `RulesService`
5. **Controllers & Routes**
   - Web & minimal REST per contract
6. **Views**
   - Dashboard, Children CRUD, Schedules show (card grid with live counters), Waitlist panel, Rules form
   - Export templates for PDF/PNG
7. **Auth & Security**
   - Registration, email verification, password recovery, lockout/ratelimit
8. **i18n**
   - Locale files de/en; language switcher
9. **Landing Page**
   - Marketing copy, pricing, CTA
10. **QA**
   - PHPUnit suites: Unit, Integration, Feature
   - PHPStan level 8, CI workflow (GitHub Actions)

---

## 10. Sample CI (to be created by Copilot)

- GitHub Actions:
  - `php-actions/composer@v6`
  - setup PHP 8.4, run `composer test`
  - run `phpstan analyse`
  - cache Composer and npm (if used)

---

## 11. UX Notes (for implementation)

- Card layout resembles the sample image; show used vs capacity (e.g., `7/9`), with integrative badges `×2`.
- Day cards show the **start child** selector.
- Sibling groups displayed with a small "family" icon.
- Drag & drop across days; capacity meter turns red on overflow, prevents save.
- Export matches the A4 multi-card aesthetic.

---

## 12. Glossary

- **Schedule**: The whole plan for a period (e.g., a week), containing many **ScheduleDays**.
- **ScheduleDay**: A single day/card in the plan with a capacity.
- **Assignment**: Link of child to a day with a weight (1 or 2).
- **Waitlist**: Global list per schedule used to fill remaining slots.
- **Start Child**: Per-day pointer into the waitlist to rotate fairness.
- **Sibling Group**: Children that must be placed together (atomic).

---

## 13. Getting Started (dev placeholder)

```bash
# Copilot will generate these files; outline for humans:
composer create-project cakephp/app ausfallplan
cp .env.example .env  # set DB creds
bin/cake migrations migrate
bin/cake server
```

---

## 14. Legal & Privacy

Include **Impressum** and **Datenschutz** (GDPR). Provide data export/delete options for organizations.

---

## 15. Roadmap (optional)
- OAuth/SSO (Google, Microsoft) for enterprise
- Theming for exports
- Mobile-friendly kiosk view
- Audit log
