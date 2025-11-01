# Ausfallplan-Generator — Complete Implementation Concept

> **Created:** Oct 22, 2025 06:17  
> **Status:** Concept Phase  
> **Goal:** Full implementation with Docker, tests, DB setup and deployment to https://fairnestplan.z11.de

---

## 1. Architecture Overview

**Tech Stack:**
- CakePHP 5.x + PHP 8.4
- MySQL 8.0
- Redis (sessions + cache)
- Docker (local dev)
- Nginx (production reverse proxy)
- GitHub Actions (CI/CD)

**Deployment Targets:**
- **Local:** `http://ausfallplan-local` (Docker)
- **Production:** `https://fairnestplan.z11.de`

---

## 2. Directory Structure

```
/var/www/Ausfallplan-Generator/
├── docker/                       # Docker configs
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── nginx/default.conf
│   ├── php/php.ini
│   └── mysql/init.sql
├── app/                          # CakePHP application
│   ├── src/
│   │   ├── Controller/
│   │   ├── Model/Entity/
│   │   ├── Model/Table/
│   │   ├── Service/             # Business logic
│   │   └── Command/
│   ├── templates/
│   ├── tests/
│   │   ├── TestCase/
│   │   └── Fixture/
│   └── composer.json
├── migrations/
├── seeds/
├── deploy/
│   ├── deploy.sh
│   └── post-deploy.sh
├── .github/workflows/
│   ├── test.yml
│   └── deploy.yml
└── README.md
```

---

## 3. Docker Configuration

### 3.1 docker-compose.yml

```yaml
version: '3.8'
services:
  app:
    build: ./docker
    volumes:
      - ./app:/var/www/html
    environment:
      - DATABASE_URL=mysql://ausfallplan:secret@db:3306/ausfallplan_dev
    depends_on:
      - db
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./app/webroot:/var/www/html/webroot
    depends_on:
      - app

  db:
    image: mysql:8.0
    environment:
      - MYSQL_DATABASE=ausfallplan_dev
      - MYSQL_USER=ausfallplan
      - MYSQL_PASSWORD=secret
      - MYSQL_ROOT_PASSWORD=secret
    volumes:
      - ./docker/mysql/data:/var/lib/mysql

volumes:
  mysql-data:
```

### 3.2 Local Setup Commands

```bash
# 1. Add host entry
sudo bash -c 'echo "127.0.0.1 ausfallplan-local" >> /etc/hosts'

# 2. Start containers
docker compose up -d

# 3. Install dependencies
docker compose exec app composer install

# 4. Run migrations & seeds
docker compose exec app bin/cake migrations migrate
docker compose exec app bin/cake migrations seed

# 5. Access
open http://ausfallplan-local
```

---

## 4. Database Schema

**8 Core Tables:**
1. `organizations` - Multi-tenant orgs
2. `users` - Admin/editor/viewer roles
3. `children` - Kids with integrative flag
4. `sibling_groups` - Atomic placement units
5. `schedules` - Plans with draft/final state
6. `schedule_days` - Individual days with capacity
7. `assignments` - Child→Day mappings
8. `waitlist_entries` - Priority-based backfill
9. `rules` - Per-schedule JSON config

**Migrations:** Versioned in `migrations/` directory
**Seeds:** Demo data in `seeds/DemoSeeder.php`

---

## 5. Business Logic Services

### 5.1 ScheduleBuilderService

**Methods:**
- `autoDistribute(scheduleId)` - Round-robin distribution
  - Respects sibling groups (atomic)
  - Integrative children use weight ×2
  - Enforces max_per_child rule
  - Processes always_last children last

### 5.2 WaitlistService

**Methods:**
- `applyToSchedule(scheduleId)` - Fill gaps from waitlist
  - Sorts by priority DESC
  - Uses start_child_id for rotation
  - Decrements remaining counter
  - Updates next start_child_id

### 5.3 RulesService

**Methods:**
- `getRulesForSchedule(scheduleId)` - Fetch/merge defaults + overrides
- Default rules: `integrative_weight=2`, `max_per_child=3`, `always_last=[]`

---

## 6. Testing Strategy

### 6.1 Unit Tests (90+ tests)

**Coverage:**
- Models: Validation, associations
- Services: Business logic isolation
- Target: >85% code coverage

**Key Test Cases:**
- Sibling groups placed atomically
- Integrative weighting (×2)
- Capacity never exceeded
- Waitlist priority ordering
- Start child rotation

### 6.2 Integration Tests (30+ tests)

**Coverage:**
- Authentication flow (email verification, password reset)
- Full schedule workflow (create → distribute → export)
- Waitlist application
- PDF generation

### 6.3 Test Execution

```bash
# Run all tests
docker compose exec app composer test

# Static analysis
docker compose exec app composer phpstan

# Code style
docker compose exec app composer cs-check
```

---

## 7. CI/CD Pipeline

### 7.1 GitHub Actions - test.yml

```yaml
name: Test Suite
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      db:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: ausfallplan_test
          MYSQL_USER: test
          MYSQL_PASSWORD: test
          MYSQL_ROOT_PASSWORD: test
        ports:
          - "3306:3306"
        volumes:
          - mysql-data:/var/lib/mysql
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: pdo_mysql, mbstring, intl, gd
      - run: composer install
      - run: bin/cake migrations migrate
      - run: composer test
      - run: composer phpstan
```

### 7.2 GitHub Actions - deploy.yml

```yaml
name: Deploy
on:
  push:
    tags:
      - 'v*'
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: appleboy/ssh-action@master
        with:
          host: fairnestplan.z11.de
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: cd /var/www/ausfallplan && bash deploy/deploy.sh
```

---

## 8. Production Deployment

### 8.1 deploy.sh

```bash
#!/bin/bash
set -e

echo "=== Starting Deployment ==="

git fetch --tags
git checkout ${TAG:-main}
composer install --no-dev --optimize-autoloader
bin/cake maintenance on
bin/cake migrations migrate
bin/cake cache clear_all
sudo systemctl reload php8.4-fpm
bin/cake maintenance off

echo "=== Deployment Complete ==="
```

### 8.4 post-deploy.sh (First deployment only)

```bash
#!/bin/bash
# Initialize production database

export DATABASE_URL="${PROD_DATABASE_URL}"
bin/cake migrations migrate
bin/cake schema_cache build
echo "Database initialized!"
```

### 8.3 Production Database Setup

```bash
# On z11.de server
sudo mysql

CREATE DATABASE ausfallplan_prod;
CREATE USER 'ausfallplan'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON ausfallplan_prod.* TO 'ausfallplan'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run initial migration
cd /var/www/ausfallplan
export DATABASE_URL="mysql://ausfallplan:secure_password_here@localhost:3306/ausfallplan_prod"
bash deploy/post-deploy.sh
```

---

## 9. Implementation Checklist

### Phase 1: Foundation
- [ ] CakePHP skeleton setup
- [ ] Docker environment (local dev)
- [ ] Database migrations (all 8 tables)
- [ ] Demo seeders
- [ ] /etc/hosts entry for ausfallplan-local

### Phase 2: Core Models
- [ ] Entity classes (Organization, User, Child, etc.)
- [ ] Table classes with associations
- [ ] Validation rules
- [ ] Unit tests for models

### Phase 3: Business Logic
- [ ] ScheduleBuilderService
- [ ] WaitlistService
- [ ] RulesService
- [ ] ExportService (PDF/PNG)
- [ ] Unit tests for services (90+ tests)

### Phase 4: Controllers & Views
- [ ] Authentication (registration, login, email verification)
- [ ] Dashboard
- [ ] Children CRUD + CSV import
- [ ] Schedules CRUD + day grid UI
- [ ] Waitlist panel
- [ ] Drag & drop (HTMX/Alpine.js)

### Phase 5: Export & i18n
- [ ] PDF export templates
- [ ] PNG export (spatie/browsershot)
- [ ] German localization (de_DE)
- [ ] English localization (en_US)
- [ ] Language switcher

### Phase 6: Landing Page
- [ ] Marketing copy
- [ ] Pricing tiers display
- [ ] Screenshots
- [ ] Impressum & Datenschutz (GDPR)

### Phase 7: Security
- [ ] Rate limiting (Redis-based)
- [ ] Login lockout (5 attempts)
- [ ] CSRF protection
- [ ] Security headers
- [ ] Password reset flow

### Phase 8: Testing
- [ ] 90+ unit tests
- [ ] 30+ integration tests
- [ ] Fixtures for all entities
- [ ] PHPStan level 8
- [ ] >85% code coverage

### Phase 9: CI/CD
- [ ] GitHub Actions test workflow
- [ ] GitHub Actions deploy workflow
- [ ] deploy.sh script
- [ ] post-deploy.sh script

### Phase 10: Production
- [ ] z11.de server setup
- [ ] Nginx vhost config
- [ ] SSL certificate (Let's Encrypt)
- [ ] MySQL production DB
- [ ] Redis production
- [ ] First deployment + DB init
- [ ] Backup strategy
- [ ] Monitoring setup

---

## 10. Environment Variables

**Local (.env):**
```
APP_NAME="Ausfallplan Generator"
DEBUG=true
DATABASE_URL="mysql://ausfallplan:secret@db:3306/ausfallplan_dev"
REDIS_URL="redis://redis:6379"
SMTP_HOST="mailhog"
SMTP_PORT=1025
```

**Production (.env):**
```
APP_NAME="Ausfallplan Generator"
DEBUG=false
DATABASE_URL="mysql://ausfallplan:PASSWORD@localhost:3306/ausfallplan_prod"
REDIS_URL="redis://localhost:6379"
SECURITY_SALT="generate-with-cake-console"
SMTP_HOST="smtp.z11.de"
SMTP_PORT=587
SMTP_USERNAME="noreply@fairnestplan.z11.de"
SMTP_PASSWORD="smtp_password"
STRIPE_SECRET_KEY="sk_live_..."
```

---

## 11. Key Features Implementation Notes

### 11.1 Sibling Groups (Atomic Placement)
- Load all children in group
- Calculate total weight (sum individual weights)
- Only place if total ≤ remaining capacity
- Create multiple assignments in same transaction

### 11.2 Integrative Children
- Fetch `integrative_weight` from rules (default: 2)
- Use this weight in capacity calculations
- Display ×2 badge in UI

### 11.3 Waitlist Rotation
- Sort entries by `priority DESC, created ASC`
- Start from `start_child_id` if set
- After successful placement:
  - Decrement `remaining`
  - Update day's `start_child_id` to next in sequence

### 11.4 PDF Export
- Use dompdf or spatie/browsershot
- Multi-card layout (like sample image)
- Show capacity badges (7/9)
- Integrative badge (×2)
- Sibling group icon
- Localized labels

---

## 12. Production Server Requirements

**Minimum Specs:**
- 2 vCPU
- 4 GB RAM
- 40 GB SSD
- Ubuntu 22.04 LTS

**Software:**
- Nginx 1.22+
- PHP 8.4-FPM
- MySQL 8.0
- Redis 7
- Composer 2
- Git

**Security:**
- UFW firewall (allow 22, 80, 443)
- Fail2ban
- SSL/TLS (Let's Encrypt)
- Regular backups

---

## 13. Next Steps

1. **Fix encoding in README.md** (replace â€" with --, â€™ with ', etc.)
2. **Create Docker environment** (Dockerfile, docker-compose.yml, nginx config)
3. **Initialize CakePHP skeleton** in `app/` directory
4. **Create migrations** for all 8 tables
5. **Implement core services** with unit tests
6. **Build UI** with HTMX/Alpine.js
7. **Setup CI/CD** pipeline
8. **Deploy to z11.de**

---

## 14. Development Commands Quick Reference

```bash
# Docker
docker compose up -d              # Start all services
docker compose exec app bash      # Shell into app container
docker compose logs -f app        # Follow app logs

# Database
bin/cake migrations migrate       # Run migrations
bin/cake migrations seed          # Seed demo data
bin/cake migrations rollback      # Undo last migration

# Testing
composer test                     # Run PHPUnit
composer phpstan                  # Static analysis
composer cs-check                 # Code style check
composer cs-fix                   # Auto-fix code style

# Cache
bin/cake cache clear_all          # Clear all caches
bin/cake schema_cache build       # Rebuild schema cache

# Production
bash deploy/deploy.sh             # Deploy to production
bash deploy/post-deploy.sh        # First-time DB setup
```

---

## 15. Demo Credentials (Local Only)

**After seeding:**
- Admin: `admin@demo.kita` / `password`
- Editor: `editor@demo.kita` / `password`
- Viewer: `viewer@demo.kita` / `password`

**MailHog UI:** `http://localhost:8025`

---

## Summary

This concept provides a complete roadmap for implementing the Ausfallplan-Generator with:
- ✅ Docker-based local development (`ausfallplan-local`)
- ✅ Production deployment to `fairnestplan.z11.de`
- ✅ Automated DB migrations + initialization
- ✅ Comprehensive testing (90+ unit, 30+ integration)
- ✅ CI/CD pipeline (GitHub Actions)
- ✅ All business logic services
- ✅ Security best practices
- ✅ i18n (DE/EN)

Ready for implementation!
