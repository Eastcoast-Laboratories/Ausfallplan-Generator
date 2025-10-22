import { test, expect } from '@playwright/test';

/**
 * Comprehensive German Translation Verification
 * Tests that ALL UI elements are properly translated to German
 */
test.describe('German Translation Verification', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@eastcoast-labs.de');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('/dashboard');
    });

    test('Login page should be in German', async ({ page }) => {
        await page.goto('/users/logout');
        await page.waitForURL('/login');
        
        // Check login page translations
        await expect(page.locator('legend')).toContainText('Anmelden');
        await expect(page.getByText('Bitte geben Sie Ihre E-Mail und Ihr Passwort ein')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toContainText('Anmelden');
        await expect(page.getByText('Neues Konto erstellen')).toBeVisible();
        
        // Check form labels
        await expect(page.locator('label[for="email"]')).toContainText('E-Mail');
        await expect(page.locator('label[for="password"]')).toContainText('Passwort');
    });

    test('Navigation menu should be in German', async ({ page }) => {
        // Check main navigation
        await expect(page.getByText('Übersicht')).toBeVisible(); // Dashboard
        await expect(page.getByText('Kinder')).toBeVisible(); // Children
        await expect(page.getByText('Geschwistergruppen')).toBeVisible(); // Sibling Groups
        await expect(page.getByText('Ausfallpläne')).toBeVisible(); // Schedules
        await expect(page.getByText('Nachrückliste')).toBeVisible(); // Waitlist
    });

    test('Dashboard should be in German', async ({ page }) => {
        await page.goto('/dashboard');
        
        // Check dashboard elements
        await expect(page.getByText('Willkommen zurück')).toBeVisible();
        await expect(page.getByText('Schnellaktionen')).toBeVisible();
        await expect(page.getByText('Kind hinzufügen')).toBeVisible();
        await expect(page.getByText('CSV importieren')).toBeVisible();
    });

    test('Children index should be in German', async ({ page }) => {
        await page.goto('/children');
        
        // Page title
        await expect(page.locator('h3')).toContainText('Kinder');
        
        // Button
        await expect(page.getByText('Neues Kind')).toBeVisible();
        
        // Table headers
        await expect(page.getByText('Name')).toBeVisible();
        await expect(page.getByText('Status')).toBeVisible();
        await expect(page.getByText('Integrativ')).toBeVisible();
        await expect(page.getByText('Geschwistergruppe')).toBeVisible();
        await expect(page.getByText('Erstellt')).toBeVisible();
        await expect(page.getByText('Aktionen')).toBeVisible();
    });

    test('Children add form should be in German', async ({ page }) => {
        await page.goto('/children/add');
        
        // Legend
        await expect(page.locator('legend')).toContainText('Kind hinzufügen');
        
        // Form labels
        await expect(page.locator('label[for="name"]')).toContainText('Name');
        await expect(page.locator('label[for="is-active"]')).toContainText('Aktiv');
        await expect(page.locator('label[for="is-integrative"]')).toContainText('Integratives Kind');
        await expect(page.locator('label[for="sibling-group-id"]')).toContainText('Geschwistergruppe');
        
        // Submit button
        await expect(page.locator('button[type="submit"]')).toContainText('Absenden');
    });

    test('Schedules index should be in German', async ({ page }) => {
        await page.goto('/schedules');
        
        // Page title
        await expect(page.locator('h3')).toContainText('Ausfallpläne');
        
        // Button
        await expect(page.getByText('Neuer Ausfallplan')).toBeVisible();
        
        // Table headers
        await expect(page.getByText('Titel')).toBeVisible();
        await expect(page.getByText('Start')).toBeVisible();
        await expect(page.getByText('Ende')).toBeVisible();
        
        // Action buttons - CRITICAL: Check "Ausfallplan generieren" not "Generate List"
        const generateButton = page.locator('a.button').filter({ hasText: 'Ausfallplan generieren' });
        if (await generateButton.count() > 0) {
            await expect(generateButton.first()).toBeVisible();
            // Make sure English version is NOT visible
            await expect(page.locator('a.button').filter({ hasText: 'Generate List' })).toHaveCount(0);
        }
        
        await expect(page.getByText('Kinder verwalten')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Bearbeiten' }).first()).toBeVisible();
    });

    test('Waitlist page should be in German', async ({ page }) => {
        await page.goto('/waitlist');
        
        // Check dropdown
        await expect(page.getByText('Ausfallplan wählen')).toBeVisible();
        
        // Check sections
        await expect(page.getByText('Verfügbare Kinder')).toBeVisible();
        await expect(page.getByText('Kinder auf der Nachrückliste')).toBeVisible();
        await expect(page.getByText('Ziehen zum Sortieren')).toBeVisible();
    });

    test('Sibling Groups should be in German', async ({ page }) => {
        await page.goto('/sibling-groups');
        
        // Page title
        await expect(page.locator('h3')).toContainText('Geschwistergruppen');
        
        // Button
        await expect(page.getByText('Neue Geschwistergruppe')).toBeVisible();
    });

    test('User menu should be in German', async ({ page }) => {
        // Hover over user avatar
        await page.hover('.user-avatar');
        
        // Check dropdown items
        await expect(page.getByText('Einstellungen')).toBeVisible();
        await expect(page.getByText('Mein Konto')).toBeVisible();
        await expect(page.getByText('Abmelden')).toBeVisible();
    });

    test('Language switcher should show German flag', async ({ page }) => {
        // Check that German flag is shown
        const languageFlag = page.locator('.language-flag');
        await expect(languageFlag).toContainText('🇩🇪');
    });

    test('Profile page should be in German', async ({ page }) => {
        await page.goto('/users/profile');
        
        // Check page title and sections
        await expect(page.locator('h1')).toContainText('Profil-Einstellungen');
        await expect(page.getByText('Verwalten Sie Ihre Kontoinformationen')).toBeVisible();
        await expect(page.getByText('Konto-Informationen')).toBeVisible();
        await expect(page.getByText('Passwort ändern')).toBeVisible();
        
        // Check form labels
        await expect(page.locator('label').filter({ hasText: 'E-Mail-Adresse' })).toBeVisible();
        await expect(page.locator('label').filter({ hasText: 'Rolle' })).toBeVisible();
        
        // Check button
        await expect(page.getByText('Änderungen speichern')).toBeVisible();
    });

    test('NO English text should be visible on main pages', async ({ page }) => {
        const pagesToCheck = [
            { url: '/dashboard', forbidden: ['Dashboard', 'Quick Actions', 'Add Child', 'Import CSV'] },
            { url: '/children', forbidden: ['Children', 'New Child', 'Active', 'Inactive', 'Integrative Child', 'Sibling Group'] },
            { url: '/schedules', forbidden: ['Schedules', 'New Schedule', 'Generate List', 'Generate Report', 'Manage Children'] },
            { url: '/waitlist', forbidden: ['Waitlist', 'Select Schedule', 'Available Children', 'Children on Waitlist'] },
            { url: '/sibling-groups', forbidden: ['Sibling Groups', 'New Sibling Group'] },
        ];

        for (const { url, forbidden } of pagesToCheck) {
            await page.goto(url);
            
            for (const text of forbidden) {
                // These English texts should NOT be visible
                const count = await page.getByText(text, { exact: true }).count();
                if (count > 0) {
                    console.error(`❌ Found English text "${text}" on ${url}`);
                }
                expect(count).toBe(0);
            }
        }
    });
});
