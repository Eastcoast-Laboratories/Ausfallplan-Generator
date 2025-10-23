# 📧 Email Debug System - Localhost

## 23.10.2025, 20:25 Uhr - "Email Debug Display"

---

## 🎯 Problem gelöst:

**Vorher:**
- Emails wurden nur geloggt
- Schwer zu testen (Logs lesen, Token copy-paste)
- Keine Möglichkeit, Links direkt zu testen

**Jetzt:**
- Emails werden auf einer Web-Seite angezeigt
- Links sind direkt klickbar
- Perfekt für lokales Testing

---

## 🚀 Wie es funktioniert:

### 1. **EmailDebugService**
- Erkennt automatisch localhost
- **Localhost:** Emails in Session speichern
- **Production:** Echtes Email-Sending (TODO: SMTP)

### 2. **Debug-Seite**
- URL: `http://localhost:8080/debug/emails`
- Zeigt alle "gesendeten" Emails
- Direkt klickbare Links
- Refresh & Clear Funktionen

### 3. **Auto-Integration**
- Alle Email-Sending-Calls nutzen jetzt `EmailDebugService::send()`
- Registration → Email mit Verify-Link
- Password Reset → Email mit Reset-Code

---

## 📋 Features:

### Debug Email Page (`/debug/emails`)

✅ **Zeigt alle Emails** mit:
- To, Subject, Body
- Timestamp
- Action Links (klickbar!)
- Raw Data (JSON)

✅ **Actions:**
- 🔄 Refresh: Seite neu laden
- 🗑️ Clear All: Alle Emails löschen
- ← Back to App: Zurück zum Dashboard

✅ **Email-Karten zeigen:**
- 📧 Subject (Header)
- To-Address
- Body (monospace, pre-wrap)
- 🔗 Klickbare Links (blauer Button)
- Raw JSON Data (collapsed)

---

## 💻 Verwendung:

### Im Controller:
```php
use App\Service\EmailDebugService;

// Send email (auto-detects localhost)
EmailDebugService::send([
    'to' => $user->email,
    'subject' => 'Verify your email',
    'body' => 'Click the link: ' . $verifyUrl,
    'links' => [
        'Verify Email' => $verifyUrl,
        'Reset Password' => $resetUrl
    ],
    'data' => [
        'user_id' => $user->id,
        'token' => $token
    ]
]);
```

### Als User:
1. Registriere dich auf localhost
2. Flash message zeigt: "View Emails" Link
3. Klicke auf Link → Siehst Email
4. Klicke "Verify Email" Button → Verifiziert!

---

## 🎨 Design:

### Email-Karte:
```
╔════════════════════════════════════════════╗
║  📧 Verify your email address       [SENT] ║
║  ────────────────────────────────────────  ║
║  To: user@example.com                      ║
║  2025-10-23 20:25:30                       ║
║                                            ║
║  ┌──────────────────────────────────────┐ ║
║  │ Hello,                               │ ║
║  │                                      │ ║
║  │ Please verify your email...         │ ║
║  └──────────────────────────────────────┘ ║
║                                            ║
║  🔗 Action Links:                          ║
║  [Verify Email →]  /users/verify/abc123    ║
║                                            ║
╚════════════════════════════════════════════╝
```

---

## 📦 Implementierte Emails:

### 1. **Registration Email**
```
To: {user.email}
Subject: Verify your email address

Body:
Hello,

Please verify your email address by clicking the link below:

{verifyUrl}

If you did not register, please ignore this email.

Links:
- Verify Email → /users/verify/{token}
```

### 2. **Password Reset Email**
```
To: {user.email}
Subject: Password Reset Code

Body:
Hello,

Your password reset code is: {6-digit-code}

This code will expire in 1 hour.

If you did not request a password reset, please ignore this email.

Links:
- Reset Password → /users/reset-password
```

---

## 🔧 Technische Details:

### Files erstellt:
```
src/Service/EmailDebugService.php
  - send()         → Send or store email
  - isLocalhost()  → Check environment
  - storeEmail()   → Store in session
  - getEmails()    → Retrieve all emails
  - clearEmails()  → Delete all emails

src/Controller/DebugController.php
  - emails()       → Show email list
  - clearEmails()  → Clear action
  - beforeFilter() → Localhost-only check

templates/Debug/emails.php
  - Beautiful email card design
  - Clickable action links
  - Responsive layout
```

### Session Storage:
```php
$session->write('Debug.emails', [
    [
        'to' => 'user@test.com',
        'subject' => 'Verify email',
        'body' => '...',
        'links' => ['Verify' => 'url'],
        'data' => ['token' => '...'],
        'timestamp' => DateTime
    ],
    // ... up to 20 emails
]);
```

### Localhost Detection:
```php
$host = $_SERVER['HTTP_HOST'];
// Matches: localhost, localhost:8080, 127.0.0.1, etc.
```

---

## 🎯 User Workflow:

### Registration mit Email-Verify:
```
1. User: /register
2. Fills form, submits
3. Flash: "Registration successful. View Emails"
4. User clicks "View Emails" link
5. Browser: /debug/emails
6. Sieht Email-Karte mit Verify-Link
7. Klickt "Verify Email" Button
8. Token wird verifiziert
9. User kann sich einloggen
```

### Password Reset:
```
1. User: /users/forgot-password
2. Gibt Email ein
3. Flash: "Reset code sent. Check your email"
4. User: /debug/emails
5. Sieht Email mit 6-digit Code
6. Kopiert Code
7. User: /users/reset-password
8. Gibt Code ein
9. Setzt neues Passwort
```

---

## 🌐 Production vs Localhost:

| Feature | Localhost | Production |
|---------|-----------|------------|
| Email Sending | ❌ Disabled | ✅ SMTP |
| Storage | 📦 Session | - |
| Debug Page | ✅ Enabled | ❌ Disabled |
| Flash Links | ✅ Shown | ❌ Hidden |
| Logs | ✅ + Display | ✅ Only logs |

---

## 🎉 Vorteile:

✅ **Kein SMTP Setup nötig** für lokales Dev  
✅ **Alle Emails sichtbar** auf einer Seite  
✅ **Links direkt klickbar** (kein Copy-Paste!)  
✅ **Timestamps** zum Debuggen  
✅ **Raw Data** für Details  
✅ **Auto-Clear** (max 20 Emails)  
✅ **Schönes Design** - macht Spaß zu nutzen  
✅ **Localhost-Only** - Sicher für Production  

---

## 🔐 Sicherheit:

**Localhost-Check:**
- Debug-Controller nur auf localhost
- Production: Redirect mit Fehlermeldung
- Keine sensiblen Daten exposen

**Session-based:**
- Emails nur in eigener Session
- User A sieht nicht Emails von User B
- Max 20 Emails (Memory-Limit)

---

## 🚀 Ergebnis:

**Jetzt auf localhost:**
1. Registriere User → Email erscheint auf `/debug/emails`
2. Klicke Verify-Link → Direkt verifiziert
3. Request Password Reset → Code auf Debug-Seite
4. Keine Logs durchsuchen mehr!
5. Kein Copy-Paste von Token!
6. Alles visuell und klickbar!

**Perfect for local development! 🎯**
