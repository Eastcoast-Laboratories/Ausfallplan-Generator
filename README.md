# Ausfallplan-Generator

> A multi-tenant web application for childcare organizations (Kitas) to create and manage day schedules (Ausfallpläne) with automatic distribution, waitlist management, and beautiful PDF/PNG exports.

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue)](https://www.php.net/)
[![CakePHP](https://img.shields.io/badge/CakePHP-5.x-red)](https://cakephp.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-blue)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue)](https://www.docker.com/)

---

## Project Status

**Status:** ✅ Concept Phase Complete — Ready for Implementation

**Documentation:**
- [📘 README.md](dev/README.md) - Complete project blueprint (encoding fixed)
- [📋 CONCEPT.md](dev/CONCEPT.md) - Full implementation concept
- [✅ IMPLEMENTATION_CHECKLIST.md](dev/IMPLEMENTATION_CHECKLIST.md) - Detailed task breakdown

---

## Quick Overview

### What It Does

- **Multi-tenant:** Each organization (Kita) has isolated data
- **Schedule Management:** Create weekly/period schedules with multiple days
- **Smart Distribution:** Automatic fair distribution with round-robin algorithm
- **Sibling Groups:** Children in same family placed together (atomically)
- **Integrative Support:** Children with special needs count double (configurable weight)
- **Waitlist:** Priority-based backfill with rotation fairness
- **Drag & Drop:** Manual adjustments with capacity validation
- **Export:** Beautiful PDF/PNG posters for printing
- **i18n:** German & English support

### Key Features

✅ Automatic distribution algorithm  
✅ Sibling group atomic placement  
✅ Integrative children weighting (×2)  
✅ Priority waitlist with rotation  
✅ Capacity constraints (never exceeded)  
✅ Max-per-child rules  
✅ Always-last processing  
✅ PDF/PNG export  
✅ HTMX + Alpine.js UI  
✅ Role-based access (admin/editor/viewer)  
✅ Email verification  
✅ Rate limiting & lockout  
✅ CSV import  

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8.4 + CakePHP 5 |
| Database | MySQL 8.0 |
| Cache/Sessions | Redis 7 |
| Frontend | HTMX + Alpine.js |
| PDF Export | dompdf |
| PNG Export | spatie/browsershot |
| Container | Docker + docker-compose |
| Web Server | Nginx (production) |
| CI/CD | GitHub Actions |
| Testing | PHPUnit + PHPStan Level 8 |

---

## Local Development

### Prerequisites

- Docker & docker-compose
- Make (optional)
- Git

### Setup (5 minutes)

```bash
# 1. Clone repository
git clone <repo-url> /var/www/Ausfallplan-Generator
cd /var/www/Ausfallplan-Generator

# 2. Add local host entry
sudo bash -c 'echo "127.0.0.1 ausfallplan-local" >> /etc/hosts'

# 3. Copy environment file
cp app/config/.env.example app/config/.env

# 4. Start Docker containers
docker-compose up -d

# 5. Install dependencies
docker-compose exec app composer install

# 6. Run database migrations
docker-compose exec app bin/cake migrations migrate

# 7. Seed demo data
docker-compose exec app bin/cake migrations seed

# 8. Access application
open http://ausfallplan-local
```

### Demo Login Credentials

After seeding, login with:

- **Admin:** `admin@demo.kita` / `password`
- **Editor:** `editor@demo.kita` / `password`
- **Viewer:** `viewer@demo.kita` / `password`

### Development Commands

```bash
# Run tests
docker-compose exec app composer test

# Static analysis
docker-compose exec app composer phpstan

# Code style check
docker-compose exec app composer cs-check

# Fix code style
docker-compose exec app composer cs-fix

# Clear caches
docker-compose exec app bin/cake cache clear_all

# Shell into container
docker-compose exec app bash

# View logs
docker-compose logs -f app

# Check MailHog (email testing)
open http://localhost:8025
```

---

## Production Deployment

### Target

**URL:** https://ausfallplan-generator.z11.de

### First Deployment

```bash
# 1. Setup server (Ubuntu 22.04)
# Install: Nginx, PHP 8.4-FPM, MySQL 8.0, Redis, Composer

# 2. Create database
sudo mysql
CREATE DATABASE ausfallplan_prod;
CREATE USER 'ausfallplan'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ausfallplan_prod.* TO 'ausfallplan'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 3. Clone repository
cd /var/www
git clone <repo-url> ausfallplan
cd ausfallplan

# 4. Copy production environment
cp deploy/production.env.template app/config/.env
# Edit .env with production values

# 5. Initialize database
bash deploy/post-deploy.sh

# 6. Configure Nginx
# Copy vhost config, enable SSL (Let's Encrypt)
sudo systemctl reload nginx

# 7. Test
curl https://ausfallplan-generator.z11.de/health
```

### Subsequent Deployments

```bash
# Push a version tag to trigger CI/CD
git tag -a v1.0.0 -m "Release 1.0.0"
git push origin v1.0.0

# Or manually on server
ssh user@ausfallplan-generator.z11.de
cd /var/www/ausfallplan
bash deploy/deploy.sh
```

---

## Testing

### Test Suite

- **90+ Unit Tests** (Models + Services)
- **30+ Integration Tests** (Controllers + Workflows)
- **Target:** >85% code coverage
- **PHPStan:** Level 8 (strictest)

### Run Tests

```bash
# All tests
composer test

# With coverage
composer test -- --coverage-html coverage/

# Static analysis
composer phpstan

# Specific test
composer test -- --filter=testAutoDistributionRoundRobin
```

---

## Architecture

### Domain Model

```
Organization 1--* Users
Organization 1--* Children
Organization 1--* Schedules
Schedule 1--* ScheduleDays
Schedule 1--* WaitlistEntries
Schedule 1--* Rules
ScheduleDay 1--* Assignments
Child 0..1 -- 1 SiblingGroup
```

### Key Services

- **ScheduleBuilderService** - Auto-distribution algorithm
- **WaitlistService** - Priority-based backfill
- **RulesService** - Per-schedule configuration
- **ExportService** - PDF/PNG generation

### Business Rules

1. **Sibling Groups:** Placed atomically (all or none)
2. **Integrative Children:** Weight ×2 (configurable)
3. **Capacity:** Never exceeded, validated on every change
4. **Max Per Child:** Configurable limit per schedule
5. **Always Last:** Specific children processed in second pass
6. **Waitlist Rotation:** Start child rotates for fairness

---

## Project Structure

```
/var/www/Ausfallplan-Generator/
├── dev/                          # Documentation
│   ├── README.md                 # Blueprint (original spec)
│   ├── CONCEPT.md                # Implementation concept
│   └── IMPLEMENTATION_CHECKLIST.md
├── docker/                       # Docker configs
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── nginx/
│   ├── php/
│   └── mysql/
├── app/                          # CakePHP application
│   ├── src/
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── Service/             # Business logic
│   │   └── Command/
│   ├── templates/
│   ├── tests/
│   └── composer.json
├── migrations/                   # Database migrations
├── seeds/                        # Demo data
├── deploy/                       # Deployment scripts
│   ├── deploy.sh
│   └── post-deploy.sh
└── .github/workflows/            # CI/CD
    ├── test.yml
    └── deploy.yml
```

---

## Implementation Status

### ✅ Completed
- [x] Project concept & architecture
- [x] Complete documentation
- [x] Implementation checklist
- [x] Docker configuration planned
- [x] Database schema designed
- [x] Service layer designed
- [x] Testing strategy defined
- [x] CI/CD pipeline designed
- [x] Deployment strategy defined

### 🔄 In Progress
- [ ] Docker environment setup
- [ ] CakePHP skeleton creation
- [ ] Database migrations
- [ ] Core models
- [ ] Business logic services
- [ ] Controllers & views
- [ ] Authentication
- [ ] Testing suite
- [ ] Production deployment

### 📋 Todo
See [IMPLEMENTATION_CHECKLIST.md](dev/IMPLEMENTATION_CHECKLIST.md) for detailed breakdown.

---

## Timeline

**Estimated:** 10 weeks (2.5 months)

- Week 1: Foundation (Docker, skeleton, DB)
- Week 2: Core models
- Week 3: Business logic services
- Week 4: Authentication & authorization
- Weeks 5-6: Controllers & views
- Week 7: Landing page & exports
- Week 8: Testing
- Week 9: CI/CD
- Week 10: Production deployment

---

## Contributing

This is a private project. Implementation follows the checklist in:
`dev/IMPLEMENTATION_CHECKLIST.md`

---

## License

MIT License

---

## Contact

**Project:** Ausfallplan-Generator  
**Production:** https://ausfallplan-generator.z11.de (when deployed)  
**Local:** http://ausfallplan-local  

---

## Next Steps

1. **Start Implementation:** Begin with Phase 1 of checklist
2. **Setup Docker:** Create docker-compose.yml and Dockerfile
3. **Initialize CakePHP:** Create app skeleton
4. **Create Migrations:** Implement all 8 tables
5. **Run Tests:** Aim for >85% coverage from the start

**Ready to build! 🚀**
