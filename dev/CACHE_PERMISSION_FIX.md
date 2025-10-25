# Cache Permission Fix + Auto-Clear Cronjob

**Problem:** Permission denied beim Zugriff auf Cache-Dateien in `/tmp/cache/models/`

---

## 🔴 INITIAL ERROR

```
Warning (512): SplFileInfo::openFile(/var/www/html/tmp/cache/models/myapp_cake_model_default_schedules): 
Failed to open stream: Permission denied 
[in /var/www/html/vendor/cakephp/cakephp/src/Cache/Engine/FileEngine.php, line 384]
```

**Wann:** Beim Zugriff auf `/schedules`

---

## ✅ SOLUTION 1: Permission Fix (Sofort)

```bash
# Fix ownership - www-data muss Owner sein
docker exec ausfallplan-generator chown -R www-data:www-data /var/www/html/tmp/cache/

# Fix permissions - 775 erlaubt read/write/execute
docker exec ausfallplan-generator chmod -R 775 /var/www/html/tmp/cache/
```

**Warum das Problem auftrat:**
- Cache-Dateien wurden von root erstellt (z.B. bei CLI-Befehlen)
- Apache läuft als www-data
- www-data konnte nicht auf root-Dateien zugreifen

---

## ✅ SOLUTION 2: Auto-Clear Cronjob (Prävention)

### **Cronjob eingerichtet:**

**File:** `docker/cron/cache-clear`
```cron
# Clear CakePHP cache every minute
* * * * * www-data /usr/local/bin/php /var/www/html/bin/cake.php cache clear_all > /proc/1/fd/1 2>&1
```

**Was es tut:**
- Läuft jede Minute
- Läuft als www-data User
- Löscht alle CakePHP Caches
- Logs gehen zu stdout (Docker logs)

---

## 🔧 DOCKERFILE CHANGES

### **Added:**

1. **Cron installiert:**
```dockerfile
    sqlite3 \
    cron \  # <-- NEU
    && rm -rf /var/lib/apt/lists/*
```

2. **Cronjob setup:**
```dockerfile
# Setup cron job for cache clearing
COPY docker/cron/cache-clear /etc/cron.d/cache-clear
RUN chmod 0644 /etc/cron.d/cache-clear && \
    crontab /etc/cron.d/cache-clear && \
    touch /var/log/cron.log
```

3. **Start-Script für Cron + Apache:**
```dockerfile
# Create startup script to run both cron and apache
RUN echo '#!/bin/bash\nservice cron start\napachectl -D FOREGROUND' > /start.sh && \
    chmod +x /start.sh

# Start cron and Apache
CMD ["/start.sh"]
```

---

## 📁 NEW FILES

```
docker/
  cron/
    cache-clear  # Cron job definition
```

---

## 🎯 BENEFITS

### **Sofort-Fix (chown/chmod):**
- ✅ Behebt aktuelles Problem
- ✅ Schedules funktionieren wieder
- ✅ Keine Permission-Errors mehr

### **Cronjob (langfristig):**
- ✅ Verhindert Cache-buildup
- ✅ Automatische Bereinigung
- ✅ Keine manuellen Cache-Clears nötig
- ✅ Fresh data jede Minute

---

## 🔄 DEPLOYMENT

**Um Cronjob zu aktivieren:**
```bash
# Container neu bauen
docker compose -f docker/docker-compose.yml build app

# Container neu starten
docker compose -f docker/docker-compose.yml up -d
```

**Verify Cronjob läuft:**
```bash
# Check cron service
docker exec ausfallplan-generator service cron status

# Check crontab
docker exec ausfallplan-generator crontab -l

# Watch cron logs
docker logs -f ausfallplan-generator | grep cache
```

---

## 💡 ALTERNATIVE: Weniger frequent

Wenn jede Minute zu oft ist, in `docker/cron/cache-clear` ändern:

```cron
# Every 5 minutes
*/5 * * * * www-data /usr/local/bin/php /var/www/html/bin/cake.php cache clear_all

# Every 15 minutes
*/15 * * * * www-data /usr/local/bin/php /var/www/html/bin/cake.php cache clear_all

# Every hour
0 * * * * www-data /usr/local/bin/php /var/www/html/bin/cake.php cache clear_all
```

---

## ⚠️ WICHTIG

**Nach Dockerfile-Änderungen:**
- Container muss NEU GEBAUT werden (`docker compose build`)
- Nicht nur restart - die Cronjob-Datei muss ins Image!

**Alternative ohne Rebuild:**
- Cronjob manuell im laufenden Container einrichten
- Aber geht bei Container-Neustart verloren

---

## 📝 STATUS

- ✅ Permissions gefixt (sofort wirksam)
- ✅ Cronjob-File erstellt
- ✅ Dockerfile angepasst
- ⏳ Container rebuild erforderlich für Cronjob

**Schedules sollten jetzt funktionieren!**
