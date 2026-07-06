import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { expect, type Page } from '@playwright/test';

function clearLocalLoginThrottle() {
  if (process.env.E2E_SKIP_LOCAL_THROTTLE_CLEAR === '1') {
    return;
  }

  const baseURL = process.env.E2E_BASE_URL
    ?? process.env.PLAYWRIGHT_BASE_URL
    ?? process.env.APP_URL
    ?? 'http://mobilitycloud.test';

  if (! /mobilitycloud\.test|localhost|127\.0\.0\.1/i.test(baseURL)) {
    return;
  }

  const currentDir = path.dirname(fileURLToPath(import.meta.url));

  execFileSync('php', ['artisan', 'tinker', '--execute', [
    "foreach (['127.0.0.1', '::1', 'localhost'] as $ip) {",
    "Illuminate\\Support\\Facades\\RateLimiter::clear('livewire-rate-limiter:'.sha1('Filament\\\\Auth\\\\Pages\\\\Login|authenticate|'.$ip));",
    '}',
  ].join(' ')], {
    cwd: path.resolve(currentDir, '../../..'),
    stdio: 'ignore',
  });
}

export async function login(page: Page, email: string, password: string) {
  clearLocalLoginThrottle();
  await page.goto('/app/login');

  for (let attempt = 1; attempt <= 2; attempt += 1) {
    await page.getByLabel(/email/i).fill(email);
    await page.getByRole('textbox', { name: /password/i }).fill(password);
    await page.getByRole('button', { name: /sign in|log in|login/i }).click();

    try {
      await expect(page).not.toHaveURL(/\/app\/login/, { timeout: 5_000 });
      break;
    } catch (error) {
      if (attempt === 2) {
        throw error;
      }

      await page.waitForTimeout(500);
    }
  }

  await expect(page.getByRole('heading', { name: /Project dashboard|Platform administration/i })).toBeVisible();
}

export async function logoutByClearingSession(page: Page) {
  await page.context().clearCookies();
  await page.goto('/app/login');
}
