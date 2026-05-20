import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './Tests/E2E',
  timeout: 30000,
  retries: 0,
  use: {
    baseURL: 'https://pictureino.ddev.site',
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium', viewport: { width: 1280, height: 720 } },
    },
  ],
});
