# Docker Quick Start Guide

## Schnellstart

```bash
# Docker-Container starten (aus Hauptverzeichnis)
bash docker/docker-start.sh
```

Die Anwendung ist dann verfügbar unter: **http://localhost:8080**

## Manuelle Befehle

Alle Docker-Dateien befinden sich jetzt im `docker/` Unterordner.

### Container Management

```bash
# Container bauen
docker compose -f docker/docker-compose.yml build

# Container starten
docker compose -f docker/docker-compose.yml up -d

# Container stoppen
docker compose -f docker/docker-compose.yml down

# Logs anzeigen
docker compose -f docker/docker-compose.yml logs -f app

# In Container einloggen
docker compose -f docker/docker-compose.yml exec app bash
```

### CakePHP Befehle

```bash
# Tests ausführen
docker compose -f docker/docker-compose.yml exec app vendor/bin/phpunit

# Migrationen ausführen
docker compose -f docker/docker-compose.yml exec app bin/cake migrations migrate

# Cache leeren
docker compose -f docker/docker-compose.yml exec app bin/cake cache clear_all
```

## Projektstruktur

```
/var/www/Ausfallplan-Generator/
├── docker/                      # Alle Docker-bezogenen Dateien
│   ├── Dockerfile              # PHP 8.4 mit Ubuntu 24.04
│   ├── docker-compose.yml      # Service Definition
│   ├── docker-start.sh         # Automatisches Start-Script
│   ├── apache-vhost.conf       # Apache Konfiguration
│   ├── .dockerignore           # Docker Build Excludes
│   ├── DOCKER_README.md        # Detaillierte Dokumentation
│   └── DOCKER_SETUP_COMPLETE.md # Setup-Info
├── config/                      # CakePHP Konfiguration
├── src/                        # Application Code
├── tmp/                        # SQLite DB & Caches
│   └── app.sqlite             # Datenbank
└── ...
```

## Technologie

- **PHP**: 8.4 (via ondrej/php PPA)
- **Web Server**: Apache 2.4
- **Datenbank**: SQLite (tmp/app.sqlite)
- **Framework**: CakePHP 5.2
- **Alle Extensions**: via apt installiert (keine Kompilierung!)

## Features

✅ 18 Unit Tests (alle bestanden)  
✅ Automatische Verteilung  
✅ Geschwistergruppen  
✅ Integrative Kinder  
✅ Wartelisten-Management  
✅ Multi-Tenant Architektur  

## Troubleshooting

### Port 8080 belegt

Ändere in `docker/docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Statt 8080
```

### Container neu bauen

```bash
docker compose -f docker/docker-compose.yml down
docker compose -f docker/docker-compose.yml build --no-cache
docker compose -f docker/docker-compose.yml up -d
```

---

**Weitere Details:** Siehe `docker/DOCKER_README.md`
