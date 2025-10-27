// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  
  use: {
    baseURL: 'http://localhost:8080',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        // Chromium is stable and works well with Playwright
      },
    },
    // Firefox disabled due to AppArmor policy conflicts with system Firefox
    // System Firefox headless mode blocked by:
    // - AppArmor DBus restrictions
    // - Sandbox CLONE_NEWPID permissions
    // Use chromium instead (already installed via npx playwright install chromium)
    {
      name: 'mobile',
      use: { ...devices['iPhone 12'] },
    },
  ],

  webServer: {
    command: 'echo "Web server already running"',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
  },
});
