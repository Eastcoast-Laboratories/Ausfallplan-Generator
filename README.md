# Ausfallplan-Generator / FairnestPlan

> A production-ready multi-tenant web application for Kitas (childcare organizations) to create and manage fair absence/day plans (Ausfallpläne) with automatic distribution, waitlist management, and export capabilities.

🌐 **Live Demo**: [fairnestplan.z11.de](https://fairnestplan.z11.de/)

## Status: ✅ Production Ready

**Fully Implemented**:
- ✅ Multi-tenant architecture with organization management
- ✅ Complete authentication & authorization system
- ✅ User management with roles (System Admin, Org Admin, Editor, Viewer)
- ✅ Children management with integrative support
- ✅ Sibling group management
- ✅ Schedule creation and management
- ✅ Automatic fair distribution algorithm
- ✅ Waitlist management with priority
- ✅ Drag & drop interface for schedule ordering
- ✅ PDF/Excel/CSV export
- ✅ Internationalization (DE/EN)
- ✅ Mobile-responsive design
- ✅ Dashboard with recent activities
- ✅ E2E browser tests (Playwright)
- ✅ Comprehensive unit tests (PHPUnit)

## Features

### 🏢 Multi-Tenant Architecture
- Organization-based data isolation
- System admins can manage all organizations
- Organization admins manage their own org
- Editors can create/edit schedules and children
- Viewers have read-only access

### 👶 Children Management
- Add, edit, and delete children
- Track active/inactive status
- Support for integrative children (count double)
- Sibling group management (placed together)
- CSV import for bulk operations
- Organization-scoped data

### 📅 Schedule Management
- Create schedules with configurable days
- Set capacity per day (max counting children)
- Automatic fair distribution algorithm
- Manual drag & drop reordering
- Manage children on schedule
- Active schedule selection
- Generate reports (PDF/Excel/CSV)

### 🎯 Smart Distribution Algorithm
- Fair round-robin distribution
- Respects capacity limits per day
- Integrative children count double
- Sibling groups placed atomically (all or none)
- "Always at end" children placed last
- Tracks assignments per child
- First-on-waitlist child shown per day

### 📋 Waitlist Management
- Priority-based waitlist per schedule
- Drag & drop priority ordering
- Move children between schedule and waitlist
- Track waitlist statistics
- First-on-waitlist indicator on reports

### 📊 Reports & Export
- Visual schedule grid (4 columns)
- PDF export for printing
- Excel export (.xlsx)
- CSV export
- Shows counting children sum per day
- Displays first-on-waitlist child
- Parent instructions included

### 🌐 Internationalization
- German (DE) - primary language
- English (EN) - full translation
- Language switcher in navigation
- Persistent language preference

### 📱 Mobile Responsive
- Optimized for mobile devices
- Hamburger menu on small screens
- Touch-friendly drag & drop
- Responsive tables and grids
- Mobile-first design approach

## Quick Start

### Prerequisites
- PHP 8.3+
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.5+
- Node.js 18+ (for E2E tests)
- Docker & Docker Compose (optional)

### Installation

#### Option 1: Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/Eastcoast-Laboratories/Ausfallplan-Generator.git
cd Ausfallplan-Generator

# Start Docker containers
docker compose -f docker/docker-compose.yml up -d

# Install dependencies
docker compose -f docker/docker-compose.yml exec app composer install

# Run migrations
docker compose -f docker/docker-compose.yml exec app bin/cake migrations migrate

# Visit http://localhost:8080
```

#### Option 2: Local Installation

```bash
# Clone the repository
git clone https://github.com/Eastcoast-Laboratories/Ausfallplan-Generator.git
cd Ausfallplan-Generator

# Install dependencies
composer install

# Configure database
cp config/app_local.example.php config/app_local.php
# Edit config/app_local.php with your database credentials

# Run migrations
bin/cake migrations migrate

# Start development server
bin/cake server -p 8080

# Visit http://localhost:8080
```

### First Steps

1. **Register an account** at `/users/register`
2. **Create an organization** (auto-created on first login)
3. **Add children** via "Children" menu
4. **Create a schedule** via "Schedules" → "New Schedule"
5. **Assign children** via "Manage Children" on schedule
6. **Generate report** via "Generate Schedule" button

## Running Tests

### PHPUnit (Backend Tests)

```bash
# Quick test (recommended for regular checks)
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit tests/TestCase/QuickTest.php

# Run all tests
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit

# Run with coverage (for major changes)
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --coverage-html coverage

# Run specific test
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit tests/TestCase/Service/RulesServiceTest.php
```

**Test Coverage:**
- ✅ RulesService (7 tests)
- ✅ ScheduleBuilder (2 tests)
- ✅ UsersController (authentication, registration)
- ✅ Application bootstrap tests
- ✅ QuickTest (6 tests, 23 assertions, ~5 seconds)

### Playwright (E2E Browser Tests)

```bash
# Install Playwright browsers (first time only)
npx playwright install chromium

# Run all E2E tests (with timeout)
timeout 120 npx playwright test --project=chromium

# Run specific test file
timeout 60 npx playwright test tests/e2e/navigation.spec.js --project=chromium

# Run with UI mode (interactive)
npx playwright test --ui

# Run in headed mode (see browser)
timeout 60 npx playwright test --project=chromium --headed

# Debug mode
npx playwright test --debug
```

**E2E Test Coverage:**
- ✅ Navigation visibility (logged in vs public)
- ✅ Mobile hamburger menu
- ✅ User dropdown and logout
- ✅ Language switcher (DE/EN)
- ✅ Registration flow
- ✅ Schedule creation workflow
- ✅ Children management
- ✅ Waitlist management

Screenshots are saved to `screenshots/` directory (gitignored).

## Tech Stack

### Backend
- **Framework**: CakePHP 5.2
- **Language**: PHP 8.3
- **Database**: MySQL 8.0 / MariaDB 10.5
- **ORM**: CakePHP ORM with migrations
- **Authentication**: CakePHP Authentication 3.x
- **Authorization**: CakePHP Authorization 3.x (role-based)
- **Testing**: PHPUnit 12

### Frontend
- **CSS Framework**: Custom CSS with Flexbox/Grid
- **JavaScript**: Vanilla JS + Sortable.js (drag & drop)
- **Icons**: Unicode emojis
- **Responsive**: Mobile-first design

### Export & Reports
- **PDF**: Browser print (CSS @media print)
- **Excel**: PhpSpreadsheet
- **CSV**: CakePHP CSV Response

### Development Tools
- **Docker**: Multi-container setup (app, db, phpmyadmin)
- **E2E Testing**: Playwright
- **Version Control**: Git with git-filter-repo
- **Deployment**: SSH-based deployment script

## Project Structure

```
.
├── config/
│   ├── Migrations/              # Database migrations (20+ files)
│   ├── app.php                  # Main configuration
│   ├── routes.php               # URL routing
│   └── app_local.php            # Local config (gitignored)
├── src/
│   ├── Model/
│   │   ├── Entity/              # Domain entities (10 classes)
│   │   │   ├── Organization.php
│   │   │   ├── User.php
│   │   │   ├── Child.php
│   │   │   ├── Schedule.php
│   │   │   ├── ScheduleDay.php
│   │   │   ├── Assignment.php
│   │   │   ├── WaitlistEntry.php
│   │   │   ├── SiblingGroup.php
│   │   │   ├── Rule.php
│   │   │   └── OrganizationUser.php
│   │   └── Table/               # Table classes (10 classes)
│   ├── Service/                 # Business logic
│   │   ├── RulesService.php
│   │   └── ScheduleBuilder.php
│   ├── Controller/              # Controllers (14 files)
│   │   ├── AppController.php
│   │   ├── UsersController.php
│   │   ├── ChildrenController.php
│   │   ├── SchedulesController.php
│   │   ├── WaitlistController.php
│   │   ├── SiblingGroupsController.php
│   │   ├── DashboardController.php
│   │   ├── PagesController.php
│   │   └── Admin/
│   │       ├── OrganizationsController.php
│   │       └── DashboardController.php
│   ├── Policy/                  # Authorization policies
│   └── View/                    # View helpers
├── templates/                   # View templates (59 files)
│   ├── layout/
│   │   ├── default.php          # Public layout
│   │   └── authenticated.php    # Logged-in layout
│   ├── Users/                   # Login, register, profile
│   ├── Children/                # CRUD + import
│   ├── Schedules/               # CRUD + reports
│   ├── Waitlist/                # Waitlist management
│   ├── SiblingGroups/           # Sibling management
│   ├── Dashboard/               # Dashboard
│   └── Admin/                   # Admin views
├── resources/
│   └── locales/                 # Translations (DE/EN)
│       ├── de_DE/default.php
│       └── en_US/default.php
├── tests/
│   ├── Fixture/                 # Test fixtures
│   ├── TestCase/                # PHPUnit tests
│   └── e2e/                     # Playwright E2E tests
├── webroot/                     # Public assets
│   ├── css/
│   ├── js/
│   └── img/
├── docker/                      # Docker setup
│   └── docker-compose.yml
└── dev/                         # Development tools
    ├── deploy.sh                # Deployment script
    ├── git-replace-in-history.sh
    └── TODO.md
```

## Database Schema

### Tables
- **organizations**: Multi-tenant organization data
- **users**: User accounts with email/password
- **organizations_users**: Many-to-many with roles (system_admin, org_admin, editor, viewer)
- **children**: Child records with integrative flag and organization_id
- **sibling_groups**: Family/sibling groupings
- **schedules**: Schedule periods with capacity_per_day
- **schedule_days**: Individual days within schedules (title, date, order)
- **assignments**: Child assignments to days (with order)
- **waitlist_entries**: Priority-based waitlist per schedule
- **rules**: Schedule-specific configuration (JSON values)

### Key Relationships
```
Organizations
  ├─ has many Users (through organizations_users)
  ├─ has many Children
  ├─ has many Schedules
  └─ has many SiblingGroups

Schedules
  ├─ belongs to Organization
  ├─ has many ScheduleDays
  ├─ has many WaitlistEntries
  └─ has many Rules

Children
  ├─ belongs to Organization
  ├─ belongs to SiblingGroup (optional)
  ├─ has many Assignments
  └─ has many WaitlistEntries

ScheduleDays
  ├─ belongs to Schedule
  └─ has many Assignments
```

### Indexes
- `organization_id` on all tenant-scoped tables
- `schedule_id` on schedule-related tables
- `child_id` on assignments and waitlist
- Composite indexes for common queries

## Business Logic

### Automatic Distribution Algorithm

The core algorithm ensures fair distribution of children across schedule days:

1. **Preparation Phase**
   - Load all active children assigned to schedule
   - Separate "always_at_end" children (placed last)
   - Load schedule rules (integrative weight, max per child)
   - Initialize day capacity tracking

2. **First Pass - Regular Children**
   - Round-robin placement across days
   - Check capacity before each placement
   - Integrative children count as 2 (configurable)
   - Sibling groups placed atomically (all or none)
   - Track assignments per child (respect max limit)
   - Skip days that would exceed capacity

3. **Second Pass - Always-at-End Children**
   - Same algorithm as first pass
   - Fills remaining capacity
   - Ensures these children are distributed last

4. **Capacity Management**
   - Each day has `capacity_per_day` (e.g., 9)
   - Sum of counting children must not exceed capacity
   - Integrative children count double
   - Capacity check before each placement

5. **Waitlist Integration**
   - First child on waitlist shown per day
   - Indicates who moves up if spot becomes available
   - Respects capacity limits

### Rules System

**Default Rules:**
```php
[
    'integrative_weight' => 2,      // Integrative children count double
    'always_last' => [],            // Child IDs to place last
    'max_per_child' => 10,          // Max assignments per child
]
```

**Schedule-Specific Overrides:**
Rules can be customized per schedule via the `rules` table with JSON values.

### User Roles & Permissions

| Role | Permissions |
|------|-------------|
| **System Admin** | Full access to all organizations, users, and data |
| **Org Admin** | Manage own organization, users, children, schedules |
| **Editor** | Create/edit children and schedules in own organization |
| **Viewer** | Read-only access to own organization's data |

**Authorization:**
- Implemented via CakePHP Authorization plugin
- Policy classes define resource-level permissions
- Organization-scoped queries ensure data isolation

## Deployment

### Production Deployment

The project includes an automated deployment script:

```bash
# Deploy to production
dev/deploy.sh
```

**Deployment Steps:**
1. Commits and pushes local changes to GitHub
2. SSHs to production server
3. Resets to safe commit (prevents conflicts)
4. Pulls latest changes
5. Clears cache
6. Confirms deployment

**Production Server:**
- URL: [fairnestplan.z11.de](https://fairnestplan.z11.de/)
- Server: eclabs-vm06
- Path: `/var/kunden/webs/ruben/www/fairnestplan.z11.de`

### Development Tools

**Git History Cleanup:**
```bash
# Remove file from entire history
git filter-repo --invert-paths --path dev/example.xls --force

# Replace text in entire history
dev/git-replace-in-history.sh 'old-text' 'new-text'
```

**Database Management:**
```bash
# Run migrations
bin/cake migrations migrate

# Rollback migration
bin/cake migrations rollback

# Create new migration
bin/cake bake migration MigrationName

# Update schema documentation
docker compose -f docker/docker-compose.yml exec -T db mysqldump \
  -u root -proot_secret --no-data --skip-comments --compact \
  ausfallplan > dev/database_structure.sql
```

## Roadmap

### Completed ✅
- [x] Multi-tenant architecture
- [x] User authentication & authorization
- [x] Children management with CSV import
- [x] Sibling group management
- [x] Schedule creation and management
- [x] Automatic distribution algorithm
- [x] Waitlist management
- [x] Drag & drop interfaces
- [x] PDF/Excel/CSV export
- [x] Internationalization (DE/EN)
- [x] Mobile responsive design
- [x] Dashboard with recent activities
- [x] E2E browser tests
- [x] Production deployment

### Planned 📝
- [ ] Email verification for new users
- [ ] Password recovery flow
- [ ] Audit logs for changes
- [ ] Rate limiting for API endpoints
- [ ] Advanced reporting and statistics
- [ ] Email notifications for schedule changes
- [ ] Calendar integration (iCal export)
- [ ] Mobile app (React Native)

## Contributing

This project follows CakePHP coding standards and best practices.

**Before submitting a PR:**
1. ✅ All PHPUnit tests pass
2. ✅ All Playwright E2E tests pass
3. ✅ Code follows PSR-12 standards
4. ✅ PHPDoc blocks are complete
5. ✅ New features include tests
6. ✅ Database migrations include schema update

**Development Workflow:**
```bash
# 1. Make changes
# 2. Run QuickTest
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit tests/TestCase/QuickTest.php

# 3. Run full tests with coverage (for major changes)
docker compose -f docker/docker-compose.yml exec -T app vendor/bin/phpunit --coverage-html coverage

# 4. Run E2E tests
timeout 120 npx playwright test --project=chromium

# 5. Commit (only if tests pass)
timeout 5 bash -c 'git add -A && git commit -m "feat: description"'
```

## License

MIT License - see LICENSE file for details

## Credits

Built with CakePHP 5 and modern PHP practices.

**Key Technologies:**
- CakePHP 5.2
- PHP 8.3
- MySQL 8.0
- Playwright
- PHPUnit
- Docker

---

**Live Demo**: [fairnestplan.z11.de](https://fairnestplan.z11.de/)

**GitHub**: [Eastcoast-Laboratories/Ausfallplan-Generator](https://github.com/Eastcoast-Laboratories/Ausfallplan-Generator)
