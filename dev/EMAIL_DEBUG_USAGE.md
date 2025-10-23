# 📧 Email Debug System - Schnellanleitung

## 🚀 Wie benutzen?

### 1. **Registration testen**
```
1. Öffne: http://localhost:8080/register
2. Fülle das Formular aus
3. Submit
4. Flash-Message zeigt: "View Emails" Link
5. Klicke "View Emails"
6. Browser öffnet: http://localhost:8080/debug/emails
7. Du siehst Email-Karte mit:
   - Subject: "Verify your email address"
   - Body: Link zum Verify
   - Button: "Verify Email" (direkt klickbar!)
8. Klicke "Verify Email" Button
9. ✅ Email verifiziert!
```

### 2. **Password Reset testen**
```
1. Öffne: http://localhost:8080/users/forgot-password
2. Gib Email ein
3. Submit
4. Gehe zu: http://localhost:8080/debug/emails
5. Du siehst Email mit:
   - Subject: "Password Reset Code"
   - Body: 6-digit Code (z.B. 123456)
6. Kopiere Code
7. Gehe zu: http://localhost:8080/users/reset-password
8. Gib Code ein + neues Passwort
9. ✅ Passwort zurückgesetzt!
```

---

## 🎨 Was du siehst:

### Debug Emails Page (`/debug/emails`)

```
╔════════════════════════════════════════════════════════╗
║                                                        ║
║  📧 Debug Emails (Localhost)                          ║
║                                                        ║
║  [🔄 Refresh]  [🗑️ Clear All]  [← Back to App]       ║
║                                                        ║
║  ────────────────────────────────────────────────     ║
║                                                        ║
║  2 email(s) captured                                  ║
║                                                        ║
║  ┌──────────────────────────────────────────────────┐ ║
║  │ 📧 Verify your email address          [SENT]    │ ║
║  │ ────────────────────────────────────────────     │ ║
║  │ To: newuser@test.com                            │ ║
║  │ 2025-10-23 20:30:15                             │ ║
║  │                                                  │ ║
║  │ ┌────────────────────────────────────────────┐  │ ║
║  │ │ Hello,                                     │  │ ║
║  │ │                                            │  │ ║
║  │ │ Please verify your email address by       │  │ ║
║  │ │ clicking the link below:                  │  │ ║
║  │ │                                            │  │ ║
║  │ │ http://localhost:8080/users/verify/abc... │  │ ║
║  │ └────────────────────────────────────────────┘  │ ║
║  │                                                  │ ║
║  │ 🔗 Action Links (Click to test):                │ ║
║  │  [Verify Email →]  /users/verify/abc123         │ ║
║  │                                                  │ ║
║  │  ▼ Show raw data                                │ ║
║  └──────────────────────────────────────────────────┘ ║
║                                                        ║
║  ┌──────────────────────────────────────────────────┐ ║
║  │ 📧 Password Reset Code                 [SENT]   │ ║
║  │ ────────────────────────────────────────────     │ ║
║  │ To: admin@test.com                              │ ║
║  │ 2025-10-23 20:32:45                             │ ║
║  │                                                  │ ║
║  │ ┌────────────────────────────────────────────┐  │ ║
║  │ │ Hello,                                     │  │ ║
║  │ │                                            │  │ ║
║  │ │ Your password reset code is: 123456       │  │ ║
║  │ │                                            │  │ ║
║  │ │ This code will expire in 1 hour.          │  │ ║
║  │ └────────────────────────────────────────────┘  │ ║
║  │                                                  │ ║
║  │ 🔗 Action Links:                                 │ ║
║  │  [Reset Password →]  /users/reset-password      │ ║
║  └──────────────────────────────────────────────────┘ ║
║                                                        ║
╚════════════════════════════════════════════════════════╝
```

---

## ✨ Features:

### Email-Karte zeigt:
- ✅ **Subject** (groß und fett)
- ✅ **To-Address** (Empfänger)
- ✅ **Timestamp** (wann gesendet)
- ✅ **Body** (vollständiger Text)
- ✅ **Action Links** (klickbare Buttons!)
- ✅ **Raw Data** (JSON, kollapsiert)
- ✅ **[SENT]** Badge (grün)

### Buttons:
- 🔄 **Refresh**: Seite neu laden (neue Emails)
- 🗑️ **Clear All**: Alle Emails löschen
- ← **Back to App**: Zurück zum Dashboard

---

## 💡 Warum ist das besser?

### Vorher:
```bash
# Logs durchsuchen
docker compose logs | grep "verification"
# Token copy-pasten
# Manuell URL bauen
# In Browser eingeben
```

### Jetzt:
```
1. Öffne /debug/emails
2. Klicke Button
3. Fertig!
```

**Zeit gespart: ~90%** ⚡

---

## 🔧 Technische Details:

### Localhost-Erkennung:
```php
// Auto-detects:
- localhost
- localhost:8080
- 127.0.0.1
- 127.0.0.1:8080
```

### Session-Storage:
```php
// Max 20 Emails gespeichert
// Pro User-Session
// Auto-Cleanup
```

### Security:
```php
// Debug-Controller:
- beforeFilter() checkt isLocalhost()
- Production: Access denied
- Localhost: Erlaubt
```

---

## 🎯 Use Cases:

### Development:
✅ **Email-Workflows testen** ohne SMTP  
✅ **Links sofort klickbar** (kein Copy-Paste)  
✅ **Visuelles Feedback** (schöne Karten)  
✅ **Schnelles Debuggen** (alle Emails auf einen Blick)  

### Testing:
✅ **E2E Tests** können Emails prüfen  
✅ **Integration Tests** sehen Email-Inhalt  
✅ **Manual QA** sieht was User empfängt  

---

## 🎨 Design-Highlights:

- **Gradient Backgrounds** (modern)
- **Box Shadows** (Tiefe)
- **Clickable Buttons** (blau, hover-Effekt)
- **Monospace Body** (leicht lesbar)
- **Collapsible Raw Data** (JSON)
- **Mobile Responsive** (funktioniert auf Handy)
- **Auto-Badges** (SENT-Status)

---

## 🚀 Pro-Tipps:

### Tipp 1: Bookmark it!
```
Chrome: Ctrl+D auf /debug/emails
→ Schnellzugriff beim Testen
```

### Tipp 2: Multi-Tab Workflow
```
Tab 1: /register (Form)
Tab 2: /debug/emails (Emails)
→ Schnell zwischen Tabs wechseln
```

### Tipp 3: Clear bei Bedarf
```
Viele Test-Emails?
→ "Clear All" Button
→ Sauberer Start
```

### Tipp 4: Raw Data nutzen
```
Brauchst Token direkt?
→ "Show raw data" klicken
→ JSON zeigt alle Details
```

---

## 📊 Statistik:

**Was wird erfasst:**
- ✅ Registration Emails
- ✅ Password Reset Emails
- ✅ Admin Notification Emails (zukünftig)
- ✅ Alle EmailDebugService::send() Calls

**Was wird NICHT erfasst:**
- ❌ Production Emails (SMTP)
- ❌ Emails von anderen Sessions
- ❌ Emails älter als 20 (auto-cleanup)

---

## 🎉 Zusammenfassung:

**Localhost = Visual Email Debug**
- Keine SMTP Config nötig
- Alle Emails sichtbar
- Links direkt klickbar
- Modern & Beautiful
- Perfect for Dev!

**Production = Real SMTP**
- EmailDebugService::send() verwendet SMTP
- Keine Debug-Seite
- Sichere Email-Zustellung
- (TODO: SMTP konfigurieren)

---

## 📖 Weitere Infos:

- **Vollständige Docs**: `dev/EMAIL_DEBUG_SYSTEM.md`
- **Code**: `src/Service/EmailDebugService.php`
- **Controller**: `src/Controller/DebugController.php`
- **View**: `templates/Debug/emails.php`

---

**Happy Testing! 🚀**
