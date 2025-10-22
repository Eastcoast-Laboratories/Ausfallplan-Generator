# Docker Setup für Ausfallplan-Generator

## Quick Start

```bash
# Starte die Anwendung mit einem Befehl
bash docker-start.sh
```

Die Anwendung ist dann verfügbar unter: **http://localhost:8080**

## Manuelle Installation

Falls du den automatischen Start nicht verwenden möchtest:

### 1. Konfiguration erstellen

```bash
# Kopiere die Beispiel-Konfiguration
cp config/app_local.example.php config/app_local.php

# Erstelle Verzeichnisse
mkdir -p tmp/cache/models tmp/cache/persistent tmp/cache/views tmp/sessions tmp/tests logs
chmod -R 775 tmp logs
```

### 2. Docker Container bauen und starten

```bash
# Container bauen
docker compose build

# Container starten
docker compose up -d
```

### 3. Datenbank Migrationen ausführen

```bash
docker compose exec app bin/cake migrations migrate
```

### 4. Anwendung öffnen

Öffne deinen Browser und navigiere zu: **http://localhost:8080**

## Verwendete Technologien

- **PHP**: 8.4
- **Web Server**: Apache 2.4
- **Datenbank**: SQLite (in `tmp/app.sqlite`)
- **Framework**: CakePHP 5.2

## Nützliche Befehle

### Container Management

```bash
# Container stoppen
docker compose down

# Container neu starten
docker compose restart

# Logs anzeigen
docker compose logs -f app

# In den Container einloggen
docker compose exec app bash
```

### CakePHP Befehle

```bash
# Tests ausführen
docker compose exec app vendor/bin/phpunit

# Cache leeren
docker compose exec app bin/cake cache clear_all

# Migrationen ausführen
docker compose exec app bin/cake migrations migrate

# Neue Migration erstellen
docker compose exec app bin/cake bake migration MigrationName
```

### Entwicklung

```bash
# Code-Style Check
docker compose exec app composer cs-check

# Code-Style Fix
docker compose exec app composer cs-fix

# PHPStan Analyse
docker compose exec app vendor/bin/phpstan analyse
```

## Verzeichnisstruktur

```
/var/www/Ausfallplan-Generator/
├── Dockerfile                # Docker Image Definition
├── docker compose.yml        # Docker Compose Konfiguration
├── docker-start.sh           # Automatischer Start-Script
├── docker/
│   └── apache-vhost.conf    # Apache Konfiguration
├── config/
│   └── app_local.php        # Lokale Konfiguration (wird generiert)
├── tmp/
│   └── app.sqlite          # SQLite Datenbank (wird erstellt)
└── ...
```

## Troubleshooting

### Port 8080 ist bereits belegt

Ändere den Port in `docker compose.yml`:

```yaml
ports:
  - "8081:80"  # Statt 8080
```

### Container startet nicht

```bash
# Logs prüfen
docker compose logs app

# Container komplett neu bauen
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Datenbankfehler

```bash
# Datenbank zurücksetzen
rm tmp/app.sqlite
docker compose exec app bin/cake migrations migrate
```

### Berechtigungsprobleme

```bash
# Berechtigungen korrigieren
chmod -R 775 tmp logs
```

## Produktionshinweise

⚠️ Diese Docker-Konfiguration ist für die **lokale Entwicklung** optimiert!

Für Production:
- Verwende einen dedizierten Datenbankserver (MySQL/PostgreSQL)
- Setze `DEBUG=false` in der Umgebungsvariable
- Verwende einen starken `SECURITY_SALT`
- Aktiviere HTTPS mit SSL-Zertifikaten
- Optimiere Apache/PHP für Performance
- Verwende `composer install --no-dev --optimize-autoloader`

## Features des Projekts

✅ Multi-Tenant Architektur  
✅ Automatische Verteilung von Kindern auf Tage  
✅ Geschwistergruppen (atomare Platzierung)  
✅ Integrative Kinder (gewichtete Platzierung)  
✅ Wartelisten-Management  
✅ PDF/PNG Export  
✅ Rollen-basierte Zugriffskontrolle  
✅ 18 Unit Tests (alle bestanden)  

## Support

Bei Problemen:
1. Prüfe die Logs: `docker compose logs -f app`
2. Prüfe den Container-Status: `docker compose ps`
3. Prüfe die CakePHP Logs in `logs/`

---

**Viel Erfolg mit dem Ausfallplan-Generator! 🚀**
