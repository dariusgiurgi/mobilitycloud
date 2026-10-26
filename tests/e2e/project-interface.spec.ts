import { expect, test } from '@playwright/test';
import { login } from './support/auth';
import { qaState } from './support/qa-state';

test('project navigation stays consistent and archive controls work on desktop and mobile', async ({ page }, testInfo) => {
  const state = qaState();
  await login(page, state.users.owner.email, state.password);
  const base = `/app/projects/${state.projects.documents.id}`;
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto(base);
  const navigation = page.locator('.fi-page-sub-navigation-tabs');
  const overviewWidth = (await navigation.boundingBox())!.width;
  await expect(page.getByRole('heading', { name: 'Team tasks' })).toBeVisible();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath('project-overview-desktop.png'), fullPage: true, animations: 'disabled' });
  await navigation.getByRole('link', { name: 'Settings', exact: true }).click();
  await expect(navigation).toBeVisible();
  expect(Math.abs((await navigation.boundingBox())!.width - overviewWidth)).toBeLessThan(2);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath('project-settings-desktop.png'), fullPage: true, animations: 'disabled' });
  await page.goto(`${base}/finalisation`);
  await expect(page.getByRole('navigation', { name: 'Archive preparation steps' })).toBeVisible();
  const category = page.locator('.mc-final-row').first();
  const selected = await category.getAttribute('aria-pressed');
  await category.click();
  await expect(category).toHaveAttribute('aria-pressed', selected === 'true' ? 'false' : 'true');
  await category.click();
  await expect(category).toHaveAttribute('aria-pressed', selected!);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath('project-export-desktop.png'), fullPage: true, animations: 'disabled' });
  await page.evaluate(() => document.documentElement.classList.add('dark'));
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath('project-export-dark.png'), fullPage: true, animations: 'disabled' });
  await page.evaluate(() => document.documentElement.classList.remove('dark'));
  await page.setViewportSize({ width: 390, height: 844 });
  for (const suffix of ['', '/edit', '/finalisation']) {
    await page.goto(`${base}${suffix}`);
    await expect(page.locator('.fi-page-sub-navigation-dropdown')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(1);
    await page.evaluate(() => window.scrollTo(0, 0));
  await page.screenshot({ path: testInfo.outputPath(`project-${suffix.slice(1) || 'overview'}-mobile.png`), fullPage: true, animations: 'disabled' });
  }
  await page.getByRole('link', { name: '3 Prepare & download' }).click();
  await expect(page.getByRole('heading', { name: '3. Prepare & download' })).toBeInViewport();
});
