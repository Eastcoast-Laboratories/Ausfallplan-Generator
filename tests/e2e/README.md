# E2E Tests mit Playwright

## Browser Setup (einmalig)

Chromium Browser installieren:
```bash
npm run setup:browsers
```

Dies installiert Chromium in `playwright-browsers/` (nicht im System-Cache).
**Der Browser bleibt permanent und muss nicht neu installiert werden.**

## Tests ausführen

**Alle E2E Tests:**
```bash
npm test
# oder
npx playwright test --project=chromium
```

**Einzelner Test:**
```bash
npx playwright test tests/e2e/language-switcher.spec.js --project=chromium
```

**Mit UI (interaktiv):**
```bash
npm run test:ui
```

**Mit Browser-Ansicht (headed mode):**
```bash
npm run test:headed
```

**Debug Mode:**
```bash
npm run test:debug
```

## Warum nur Chromium?

- ✅ **Stabil**: Keine AppArmor/Sandbox Konflikte
- ✅ **Schnell**: Optimiert für headless automation
- ✅ **Zuverlässig**: Playwright's eigener Build
- ✅ **Permanent**: Im Projekt gespeichert, nicht im Cache

Firefox wurde deaktiviert wegen:
- ❌ AppArmor DBus access denied
- ❌ Sandbox CLONE_NEWPID permission errors
- ❌ Crashes in headless mode

## Browser-Speicherort

```
playwright-browsers/
├── chromium-1194/           # Chromium Browser
├── chromium_headless_shell-1194/  # Headless Shell
└── ffmpeg-1011/            # Video recording support
```

**Größe:** ~280 MB
**Wird nicht gelöscht:** In `.gitignore` aber lokal permanent

## Troubleshooting

**"Executable doesn't exist" Error:**
```bash
npm run setup:browsers
```

**Tests hängen:**
- Timeout-Commands verwenden
- Server muss auf http://localhost:8080 laufen

**Browser-Cache leeren:**
```bash
rm -rf playwright-browsers/
npm run setup:browsers
```

## Configuration

Siehe `playwright.config.js`:
- Base URL: http://localhost:8080
- Projects: chromium only
- Screenshots: on failure
- Trace: on first retry
