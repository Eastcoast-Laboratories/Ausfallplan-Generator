# Ausfallplan-Generator

> A multi-tenant web application for Kitas (childcare organizations) to create and manage **Schedules** (absence/day plans) for children with automatic distribution, waitlist management, and PDF/PNG export capabilities.

## Status

✅ **Completed**:
- CakePHP 5 application structure with migrations
- Domain models (Organizations, Users, Children, Schedules, ScheduleDays, Assignments, Rules, WaitlistEntries, SiblingGroups)
- Business logic services (RulesService, ScheduleBuilder)
- Comprehensive unit tests (18 tests passing, 36 assertions)
- Automatic distribution algorithm with capacity tracking
- Sibling group support
- Integrative children weighting (configurable, default 2x)
- Landing page with feature overview

🚧 **In Progress**:
- Waitlist management service
- Authentication & authorization
- Controllers and views
- PDF/PNG export
- Internationalization (DE/EN)

## Features

### 👶 Children Management
- Track active/inactive children
- Support for integrative children (weighted assignments)
- Sibling group management (atomic placement)
- Organization-scoped data

### 📅 Schedule Management
- Create schedules with multiple days
- Configure capacity per day
- Automatic distribution algorithm
- Manual override capability
- Draft and final states

### 🎯 Smart Distribution
- Fair round-robin distribution
- Respects capacity limits
- Integrative children use configurable weight
- Sibling groups placed atomically
- Max assignments per child
- Always-last rules

### 📋 Rules System
- Integrative weight (default: 2)
- Always-last list
- Max per child limit
- Schedule-specific overrides

## Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- PostgreSQL 14+ or MySQL 8 (SQLite for testing)

### Installation

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
bin/cake server

# Visit http://localhost:8765
```

### Running Tests

#### PHPUnit (Backend Tests)

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/TestCase/Service/RulesServiceTest.php
vendor/bin/phpunit tests/TestCase/Service/ScheduleBuilderTest.php
vendor/bin/phpunit tests/TestCase/Controller/UsersControllerTest.php

# Run tests in Docker container
docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit
docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit --testdox
docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit tests/TestCase/Controller/UsersControllerTest.php

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html tmp/coverage
```

#### Playwright (E2E Browser Tests)

The project includes end-to-end browser tests using Playwright for testing user interactions, navigation, and visual components.

```bash
# Install Playwright browsers (first time only)
npx playwright install chromium

# Run all E2E tests
npm run test

# Run specific test file
npm run test:screenshots        # Navigation tests with screenshots
npx playwright test tests/e2e/navigation.spec.js

# Run tests with UI mode (interactive)
npm run test:ui

# Run tests in headed mode (see browser)
npm run test:headed

# Run specific browser
npm run test:chromium
npm run test:firefox
npm run test:webkit

# Debug mode
npm run test:debug
```

**E2E Test Coverage:**
- ✅ Navigation visibility (logged in vs public pages)
- ✅ Mobile hamburger menu functionality
- ✅ User dropdown and logout
- ✅ Language switcher (DE/EN)
- ✅ Registration flow (user not auto-logged in)
- 🚧 Schedule creation workflow (in progress)

Screenshots are saved to `screenshots/` directory (gitignored).

**Test User Credentials:**
- Email: `admin@example.com`
- Password: `password123`

## Tech Stack

- **Framework**: CakePHP 5.2
- **Language**: PHP 8.3
- **Database**: PostgreSQL/MySQL/SQLite
- **Testing**: PHPUnit 12
- **PDF Generation**: dompdf
- **Authentication**: CakePHP Authentication 3.x
- **Authorization**: CakePHP Authorization 3.x

## Project Structure

```
.
├── config/
│   ├── Migrations/           # Database migrations
│   ├── app.php              # Main configuration
│   └── routes.php           # URL routing
├── src/
│   ├── Model/
│   │   ├── Entity/          # Domain entities (9 classes)
│   │   └── Table/           # Table classes with associations (9 classes)
│   ├── Service/             # Business logic services
│   │   ├── RulesService.php
│   │   └── ScheduleBuilder.php
│   ├── Controller/          # Controllers
│   └── View/                # View layer
├── templates/               # View templates
│   └── Pages/home.php      # Landing page
├── tests/
│   ├── Fixture/            # Test fixtures (7 fixtures)
│   └── TestCase/           # Unit and integration tests
│       └── Service/        # Service tests
└── webroot/                # Public assets
```

## Database Schema

### Tables
- **organizations**: Multi-tenant organization data
- **users**: Users with roles (admin/editor/viewer)
- **children**: Child records with integrative flag
- **sibling_groups**: Family/sibling groupings
- **schedules**: Schedule periods
- **schedule_days**: Individual days within schedules
- **assignments**: Child assignments to days
- **waitlist_entries**: Priority-based waitlist
- **rules**: Schedule-specific configuration rules

### Key Relationships
- Organizations ← Users, Children, Schedules, SiblingGroups
- Schedules ← ScheduleDays, WaitlistEntries, Rules
- ScheduleDays ← Assignments
- Children ← Assignments, WaitlistEntries
- SiblingGroups ← Children

## Business Logic

### Automatic Distribution Algorithm

1. **Preparation**
   - Load active children from organization
   - Separate "always_last" children
   - Get schedule rules (integrative weight, max per child)

2. **First Pass - Normal Children**
   - Round-robin placement across days
   - Check capacity before placement
   - Handle sibling groups atomically
   - Track assignments per child
   - Apply integrative weight (default 2x)

3. **Second Pass - Always Last Children**
   - Same algorithm as first pass
   - Fills remaining capacity

4. **Capacity Tracking**
   - Each day has configurable capacity
   - Weight sums must not exceed capacity
   - Integrative children count as configured weight

### Rules System

Default rules:
- `integrative_weight`: 2 (integrative children count double)
- `always_last`: [] (empty list)
- `max_per_child`: 10 (maximum assignments per child)

Rules can be overridden per schedule by creating Rule entities with JSON values.

## Testing

The project includes comprehensive unit tests:

- **RulesServiceTest**: 7 tests covering default values and custom overrides
- **ScheduleBuilderTest**: 2 tests covering capacity limits and integrative weighting
- **ApplicationTest**: CakePHP application bootstrap tests
- **PagesControllerTest**: Controller tests

All tests use SQLite in-memory database for speed.

## Development Roadmap

See [README_BLUEPRINT.md](./README_BLUEPRINT.md) for the complete feature specification.

### Phase 1 (Completed ✅)
- [x] CakePHP structure
- [x] Database schema and migrations
- [x] Domain models and entities
- [x] Core business logic (RulesService, ScheduleBuilder)
- [x] Unit tests for services
- [x] Landing page

### Phase 2 (In Progress 🚧)
- [ ] WaitlistService implementation
- [ ] Authentication setup
- [ ] Authorization policies
- [ ] CRUD controllers
- [ ] Basic views

### Phase 3 (Planned 📝)
- [ ] PDF/PNG export
- [ ] Internationalization (DE/EN)
- [ ] Dashboard
- [ ] Schedule builder UI
- [ ] Drag & drop interface

### Phase 4 (Planned 📝)
- [ ] Email verification
- [ ] Password recovery
- [ ] Rate limiting
- [ ] Audit logs
- [ ] Integration tests

## Contributing

This project follows CakePHP coding standards. Please ensure:
- All tests pass before submitting PR
- New features include unit tests
- Code follows PSR-12 standards
- PHPDoc blocks are complete

## License

MIT License - see LICENSE file for details

## Credits

Built with CakePHP 5 and modern PHP practices.

---

For detailed architecture and feature specifications, see [README_BLUEPRINT.md](./README_BLUEPRINT.md).
