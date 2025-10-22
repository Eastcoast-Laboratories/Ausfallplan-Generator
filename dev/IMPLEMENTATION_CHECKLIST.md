# Implementation Checklist — Complete Task List

> **Project:** Ausfallplan-Generator  
> **Target:** https://ausfallplan-generator.z11.de  
> **Local:** http://ausfallplan-local  
> **Status:** Concept phase complete, ready for implementation

---

## Phase 1: Foundation Setup (Week 1)

### 1.1 Docker Environment
- [ ] Create `docker/Dockerfile` (multi-stage PHP 8.4)
- [ ] Create `docker/docker-compose.yml` (app, nginx, db, redis, mailhog)
- [ ] Create `docker/nginx/default.conf` (FastCGI config)
- [ ] Create `docker/php/php.ini` (PHP settings)
- [ ] Create `docker/mysql/init.sql` (DB initialization)
- [ ] Add `/etc/hosts` entry: `127.0.0.1 ausfallplan-local`
- [ ] Test: `docker-compose up -d` works

### 1.2 CakePHP Skeleton
- [ ] Run: `composer create-project cakephp/app app`
- [ ] Create `app/config/.env.example`
- [ ] Configure database connection (MySQL)
- [ ] Configure Redis for sessions
- [ ] Install plugins:
  - [ ] `cakephp/authentication`
  - [ ] `cakephp/authorization`
  - [ ] `dompdf/dompdf`
  - [ ] `spatie/browsershot` (for PNG export)
- [ ] Test: App loads at `http://ausfallplan-local`

### 1.3 Database Schema
- [ ] Create migration: `20251022_CreateInitialSchema.php`
  - [ ] `organizations` table
  - [ ] `users` table (with email_verified_at, last_login_at)
  - [ ] `children` table (with is_integrative, is_active, sibling_group_id)
  - [ ] `sibling_groups` table
  - [ ] `schedules` table (with state: draft/final)
  - [ ] `schedule_days` table (with capacity, position, start_child_id)
  - [ ] `assignments` table (with weight, source)
  - [ ] `waitlist_entries` table (with priority, remaining)
  - [ ] `rules` table (JSON value column)
- [ ] Create migration: `20251022_AddEmailVerification.php`
  - [ ] Add `email_verification_token` to users
  - [ ] Add `email_verification_expires_at` to users
- [ ] Create migration: `20251022_AddPasswordReset.php`
  - [ ] Add `password_reset_token` to users
  - [ ] Add `password_reset_expires_at` to users
- [ ] Create migration: `20251022_AddRateLimiting.php`
  - [ ] `login_attempts` table (ip, user_id, attempts, locked_until)
- [ ] Run: `bin/cake migrations migrate`
- [ ] Test: All tables exist

### 1.4 Demo Data Seeder
- [ ] Create `seeds/DemoSeeder.php`
  - [ ] 1 organization: "Demo Kita"
  - [ ] 3 users: admin@demo.kita, editor@demo.kita, viewer@demo.kita
  - [ ] 20 children (5 integrative, 2 sibling groups)
  - [ ] 1 schedule with 5 days
  - [ ] Waitlist with 5 entries
  - [ ] Default rules
- [ ] Run: `bin/cake migrations seed`
- [ ] Test: Can login with demo credentials

---

## Phase 2: Core Models (Week 2)

### 2.1 Entity Classes
- [ ] `src/Model/Entity/Organization.php`
- [ ] `src/Model/Entity/User.php` (password hashing, role validation)
- [ ] `src/Model/Entity/Child.php`
- [ ] `src/Model/Entity/SiblingGroup.php`
- [ ] `src/Model/Entity/Schedule.php` (state transitions)
- [ ] `src/Model/Entity/ScheduleDay.php`
- [ ] `src/Model/Entity/Assignment.php`
- [ ] `src/Model/Entity/WaitlistEntry.php`
- [ ] `src/Model/Entity/Rule.php` (JSON accessor)

### 2.2 Table Classes
- [ ] `src/Model/Table/OrganizationsTable.php`
  - [ ] hasMany: Users, Children, Schedules
- [ ] `src/Model/Table/UsersTable.php`
  - [ ] belongsTo: Organization
  - [ ] Validation: email unique per org
- [ ] `src/Model/Table/ChildrenTable.php`
  - [ ] belongsTo: Organization, SiblingGroup
  - [ ] Validation: name unique per org
- [ ] `src/Model/Table/SiblingGroupsTable.php`
  - [ ] belongsTo: Organization
  - [ ] hasMany: Children
- [ ] `src/Model/Table/SchedulesTable.php`
  - [ ] belongsTo: Organization
  - [ ] hasMany: ScheduleDays, WaitlistEntries, Rules
- [ ] `src/Model/Table/ScheduleDaysTable.php`
  - [ ] belongsTo: Schedule, StartChild (children)
  - [ ] hasMany: Assignments
- [ ] `src/Model/Table/AssignmentsTable.php`
  - [ ] belongsTo: ScheduleDay, Child
  - [ ] Validation: unique(schedule_day_id, child_id)
- [ ] `src/Model/Table/WaitlistEntriesTable.php`
  - [ ] belongsTo: Schedule, Child
  - [ ] Validation: unique(schedule_id, child_id)
- [ ] `src/Model/Table/RulesTable.php`
  - [ ] belongsTo: Schedule
  - [ ] Validation: unique(schedule_id, key)

### 2.3 Model Tests
- [ ] `tests/TestCase/Model/Table/OrganizationsTableTest.php`
- [ ] `tests/TestCase/Model/Table/UsersTableTest.php`
- [ ] `tests/TestCase/Model/Table/ChildrenTableTest.php`
- [ ] `tests/TestCase/Model/Table/SchedulesTableTest.php`
- [ ] Target: 100% model coverage

---

## Phase 3: Business Logic Services (Week 3)

### 3.1 ScheduleBuilderService
- [ ] `src/Service/ScheduleBuilderService.php`
  - [ ] `autoDistribute(scheduleId)` method
    - [ ] Load active children
    - [ ] Load sibling groups
    - [ ] Load rules (integrative_weight, max_per_child, always_last)
    - [ ] Round-robin distribution algorithm
    - [ ] Respect sibling group atomicity
    - [ ] Apply integrative weighting
    - [ ] Exclude always_last children initially
    - [ ] Process always_last in second pass
    - [ ] Create assignments with source='auto'
  - [ ] `clearAssignments(scheduleId, source)` method
  - [ ] `validateCapacity(scheduleDay)` method

### 3.2 WaitlistService
- [ ] `src/Service/WaitlistService.php`
  - [ ] `applyToSchedule(scheduleId)` method
    - [ ] Load waitlist sorted by priority DESC, created ASC
    - [ ] For each schedule_day:
      - [ ] Get current start_child_id
      - [ ] Iterate circularly from start position
      - [ ] Try to place child/group if capacity allows
      - [ ] Decrement remaining counter
      - [ ] Update start_child_id to next in sequence
    - [ ] Create assignments with source='waitlist'
  - [ ] `addToWaitlist(scheduleId, childId, priority)` method
  - [ ] `removeFromWaitlist(entryId)` method
  - [ ] `updatePriority(entryId, newPriority)` method

### 3.3 RulesService
- [ ] `src/Service/RulesService.php`
  - [ ] `getRulesForSchedule(scheduleId)` method
    - [ ] Default rules: integrative_weight=2, max_per_child=3, always_last=[]
    - [ ] Merge with schedule-specific overrides
  - [ ] `setRule(scheduleId, key, value)` method
  - [ ] `deleteRule(scheduleId, key)` method

### 3.4 ExportService
- [ ] `src/Service/ExportService.php`
  - [ ] `generatePDF(scheduleId)` method (dompdf)
  - [ ] `generatePNG(scheduleId)` method (browsershot)
  - [ ] Multi-card layout template
  - [ ] Show capacity (used/total)
  - [ ] Integrative badge (×2)
  - [ ] Sibling group icon
  - [ ] Localized labels

### 3.5 Service Tests
- [ ] `tests/TestCase/Service/ScheduleBuilderServiceTest.php`
  - [ ] testAutoDistributionRoundRobin()
  - [ ] testSiblingGroupsAtomic()
  - [ ] testIntegrativeWeighting()
  - [ ] testAlwaysLastProcessing()
  - [ ] testMaxPerChildEnforced()
  - [ ] testCapacityNeverExceeded()
- [ ] `tests/TestCase/Service/WaitlistServiceTest.php`
  - [ ] testPriorityOrdering()
  - [ ] testStartChildRotation()
  - [ ] testRemainingDecrement()
  - [ ] testStopsWhenRemainingZero()
  - [ ] testSiblingGroupsInWaitlist()
- [ ] `tests/TestCase/Service/RulesServiceTest.php`
  - [ ] testDefaultRules()
  - [ ] testScheduleOverrides()
  - [ ] testSetRule()
- [ ] `tests/TestCase/Service/ExportServiceTest.php`
  - [ ] testGeneratePDFReturnsFile()
  - [ ] testGeneratePNGReturnsImage()

---

## Phase 4: Authentication & Authorization (Week 4)

### 4.1 Authentication Setup
- [ ] Configure `cakephp/authentication` plugin
- [ ] Create `src/Controller/UsersController.php`
  - [ ] `register()` action
  - [ ] `login()` action
  - [ ] `logout()` action
  - [ ] `verifyEmail(token)` action
  - [ ] `forgotPassword()` action
  - [ ] `resetPassword(token)` action
- [ ] Create email templates:
  - [ ] `templates/email/html/email_verification.php`
  - [ ] `templates/email/html/password_reset.php`
- [ ] Implement rate limiting (Redis-based)
- [ ] Implement login lockout (5 attempts = 15 min)

### 4.2 Authorization Setup
- [ ] Configure `cakephp/authorization` plugin
- [ ] Create `src/Policy/OrganizationPolicy.php`
- [ ] Create `src/Policy/ChildPolicy.php`
- [ ] Create `src/Policy/SchedulePolicy.php`
- [ ] Role checks:
  - [ ] Admin: full access
  - [ ] Editor: manage children, schedules
  - [ ] Viewer: read-only

### 4.3 Auth Tests
- [ ] `tests/TestCase/Controller/UsersControllerTest.php`
  - [ ] testRegisterCreatesUser()
  - [ ] testLoginSuccessful()
  - [ ] testLoginFailsAfter5Attempts()
  - [ ] testEmailVerificationFlow()
  - [ ] testPasswordResetFlow()
- [ ] `tests/TestCase/Integration/AuthenticationTest.php`
  - [ ] testUnverifiedEmailCannotAccessApp()
  - [ ] testLockedOutUserCannotLogin()
  - [ ] testPasswordResetTokenExpires()

---

## Phase 5: Controllers & Views (Week 5-6)

### 5.1 Dashboard
- [ ] `src/Controller/DashboardController.php`
  - [ ] `index()` - Recent schedules, alerts
- [ ] `templates/Dashboard/index.php`
  - [ ] Schedule list with status
  - [ ] Capacity warnings
  - [ ] Quick actions

### 5.2 Children Management
- [ ] `src/Controller/ChildrenController.php`
  - [ ] `index()` - List all children
  - [ ] `add()` - Create child
  - [ ] `edit(id)` - Update child
  - [ ] `delete(id)` - Soft delete (is_active=false)
  - [ ] `import()` - CSV import
- [ ] `templates/Children/index.php`
- [ ] `templates/Children/add.php`
- [ ] `templates/Children/edit.php`
- [ ] CSV import with validation

### 5.3 Schedules Management
- [ ] `src/Controller/SchedulesController.php`
  - [ ] `index()` - List schedules
  - [ ] `add()` - Create schedule
  - [ ] `view(id)` - Day grid UI
  - [ ] `edit(id)` - Update schedule
  - [ ] `delete(id)` - Delete schedule
  - [ ] `build(id)` - Auto-distribute
  - [ ] `applyWaitlist(id)` - Fill from waitlist
  - [ ] `finalize(id)` - Set state=final
  - [ ] `exportPdf(id)` - PDF download
  - [ ] `exportPng(id)` - PNG download
- [ ] `templates/Schedules/index.php`
- [ ] `templates/Schedules/view.php` (main UI!)
  - [ ] Day cards grid
  - [ ] Live capacity counters (Alpine.js)
  - [ ] Drag & drop assignments (HTMX)
  - [ ] Waitlist panel
  - [ ] Rules editor
  - [ ] Start child picker per day
- [ ] `templates/Schedules/add.php`
- [ ] `templates/Schedules/edit.php`

### 5.4 API Endpoints
- [ ] `src/Controller/Api/SchedulesController.php`
  - [ ] `POST /api/schedules/:id/build` - Auto-distribute
  - [ ] `POST /api/schedules/:id/apply-waitlist` - Apply waitlist
  - [ ] `POST /api/schedules/:id/assignments` - Create assignment (drag & drop)
  - [ ] `DELETE /api/assignments/:id` - Remove assignment
  - [ ] `PUT /api/assignments/:id/move` - Move to different day

### 5.5 Frontend Assets
- [ ] Download HTMX: `webroot/js/htmx.min.js`
- [ ] Download Alpine.js: `webroot/js/alpine.min.js`
- [ ] Create `webroot/js/app.js` - Drag & drop logic
- [ ] Create `webroot/css/app.css` - Card layout styling
- [ ] Implement live capacity counter
- [ ] Implement drag & drop with capacity validation

---

## Phase 6: Landing Page & Localization (Week 7)

### 6.1 Landing Page
- [ ] `src/Controller/PagesController.php`
  - [ ] `home()` - Landing page
- [ ] `templates/Pages/home.php`
  - [ ] Hero section
  - [ ] Features showcase
  - [ ] Screenshot gallery
  - [ ] Pricing tiers (Test Plan, Pro, Enterprise)
  - [ ] CTA buttons (Sign Up, Free Trial)
  - [ ] Testimonials (optional)
- [ ] `templates/Pages/impressum.php` (GDPR)
- [ ] `templates/Pages/datenschutz.php` (Privacy Policy)

### 6.2 Localization
- [ ] Extract all strings: `bin/cake i18n extract`
- [ ] Translate `resources/locales/de_DE/default.po`
- [ ] Translate `resources/locales/en_US/default.po`
- [ ] Create language switcher component
- [ ] Test all views in both languages

---

## Phase 7: PDF/PNG Export (Week 7)

### 7.1 PDF Template
- [ ] `templates/pdf/schedule_export.php`
  - [ ] Multi-card layout (A4)
  - [ ] Show all schedule days
  - [ ] Display children names
  - [ ] Capacity badge (7/9)
  - [ ] Integrative badge (×2)
  - [ ] Sibling group icon
  - [ ] Localized labels
- [ ] Configure dompdf settings
- [ ] Test PDF generation

### 7.2 PNG Export
- [ ] Install Puppeteer/Chromium for browsershot
- [ ] Configure browsershot
- [ ] Test PNG generation

---

## Phase 8: Testing Suite (Week 8)

### 8.1 Unit Tests (Target: 90+)
- [ ] All Model tests
- [ ] All Service tests
- [ ] All Entity tests
- [ ] Run: `composer test`
- [ ] Verify: >85% code coverage

### 8.4 Integration Tests (Target: 30+)
- [ ] Full schedule workflow test
- [ ] Authentication flow test
- [ ] Waitlist application test
- [ ] CSV import test
- [ ] PDF export test

### 8.3 Fixtures
- [ ] Create fixtures for all entities
- [ ] Ensure fixtures have realistic data

### 8.4 Code Quality
- [ ] Configure PHPStan level 8: `phpstan.neon`
- [ ] Run: `composer phpstan`
- [ ] Fix all issues
- [ ] Configure PHP CS Fixer
- [ ] Run: `composer cs-fix`

---

## Phase 9: CI/CD Pipeline (Week 9)

### 9.1 GitHub Actions - Test Workflow
- [ ] Create `.github/workflows/test.yml`
  - [ ] Setup PHP 8.4
  - [ ] Setup MySQL service
  - [ ] Install Composer dependencies
  - [ ] Run migrations
  - [ ] Run PHPUnit tests
  - [ ] Run PHPStan
  - [ ] Upload coverage report
- [ ] Test: Push code triggers workflow

### 9.2 GitHub Actions - Deploy Workflow
- [ ] Create `.github/workflows/deploy.yml`
  - [ ] Trigger on tag push (v*)
  - [ ] SSH to z11.de server
  - [ ] Run deploy script
- [ ] Setup GitHub Secrets:
  - [ ] SSH_USER
  - [ ] SSH_KEY
  - [ ] PROD_DATABASE_URL

### 9.3 Deployment Scripts
- [ ] Create `deploy/deploy.sh`
  - [ ] Git pull/checkout tag
  - [ ] Composer install --no-dev
  - [ ] Enable maintenance mode
  - [ ] Run migrations
  - [ ] Clear caches
  - [ ] Reload PHP-FPM
  - [ ] Disable maintenance mode
- [ ] Create `deploy/post-deploy.sh` (first deploy only)
  - [ ] Initialize production database
  - [ ] Build schema cache
- [ ] Test locally with dummy deployment

---

## Phase 10: Production Deployment (Week 10)

### 10.1 Server Setup
- [ ] Provision server at z11.de
- [ ] Install Ubuntu 22.04 LTS
- [ ] Install Nginx 1.22+
- [ ] Install PHP 8.4-FPM with extensions
- [ ] Install MySQL 8.0
- [ ] Install Redis 7
- [ ] Install Composer 2
- [ ] Configure UFW firewall

### 10.2 Nginx Configuration
- [ ] Create vhost: `/etc/nginx/sites-available/ausfallplan`
- [ ] Configure FastCGI to PHP-FPM
- [ ] SSL certificate with Let's Encrypt
- [ ] Enable: `ln -s /etc/nginx/sites-available/ausfallplan /etc/nginx/sites-enabled/`
- [ ] Test: `nginx -t`
- [ ] Reload: `systemctl reload nginx`

### 10.3 Database Setup
- [ ] Create MySQL database
- [ ] Create user with password
- [ ] Grant privileges
- [ ] Test connection

### 10.4 Application Deploy
- [ ] Clone repository to `/var/www/ausfallplan`
- [ ] Copy production `.env` file
- [ ] Run `bash deploy/post-deploy.sh`
- [ ] Test: Visit https://ausfallplan-generator.z11.de
- [ ] Verify: All features work

### 10.5 Monitoring & Backups
- [ ] Setup MySQL daily backups (cron)
- [ ] Setup log rotation
- [ ] Configure error monitoring (Sentry optional)
- [ ] Setup uptime monitoring
- [ ] Document backup restoration procedure

---

## Testing Verification

### Local Testing
```bash
# Start Docker environment
docker-compose up -d

# Run migrations
docker-compose exec app bin/cake migrations migrate
docker-compose exec app bin/cake migrations seed

# Run tests
docker-compose exec app composer test
docker-compose exec app composer phpstan

# Manual testing
open http://ausfallplan-local
```

### Production Testing
```bash
# After first deploy
ssh user@ausfallplan-generator.z11.de
cd /var/www/ausfallplan
bash deploy/post-deploy.sh

# Health check
curl https://ausfallplan-generator.z11.de/health

# Manual testing
open https://ausfallplan-generator.z11.de
```

---

## Success Criteria

- [ ] All 90+ unit tests pass
- [ ] All 30+ integration tests pass
- [ ] PHPStan level 8 passes with 0 errors
- [ ] Code coverage >85%
- [ ] Local environment runs at http://ausfallplan-local
- [ ] Production environment runs at https://ausfallplan-generator.z11.de
- [ ] Database auto-migrates on deploy
- [ ] PDF export works
- [ ] PNG export works
- [ ] Both languages (DE/EN) work
- [ ] All authentication flows work
- [ ] Rate limiting prevents brute force
- [ ] Drag & drop works smoothly
- [ ] Waitlist auto-fill works correctly
- [ ] Sibling groups placed atomically
- [ ] Integrative children weighted correctly
- [ ] CI/CD pipeline deploys automatically on tag

---

## Estimated Timeline

- **Phase 1:** Foundation Setup - 1 week
- **Phase 2:** Core Models - 1 week
- **Phase 3:** Business Logic - 1 week
- **Phase 4:** Auth & Authorization - 1 week
- **Phase 5:** Controllers & Views - 2 weeks
- **Phase 6:** Landing & i18n - 1 week
- **Phase 7:** Export - 1 week
- **Phase 8:** Testing - 1 week
- **Phase 9:** CI/CD - 1 week
- **Phase 10:** Production Deploy - 1 week

**Total: 10 weeks** (2.5 months)

---

## Ready for Implementation!

All components are documented. Next step: Start with Phase 1.
