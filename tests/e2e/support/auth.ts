import { expect, type Page } from '@playwright/test';

export async function login(page: Page, email: string, password: string) {
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
