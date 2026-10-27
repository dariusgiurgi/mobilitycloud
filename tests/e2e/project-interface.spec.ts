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

test('account sections stay inside their columns with expanded and collapsed navigation', async ({ page }, testInfo) => {
  const state = qaState();
  await login(page, state.users.owner.email, state.password);
  for (const width of [1470, 1024, 768, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/app/account-settings');
    const checkColumns = async () => {
      const overflow = await page.locator('.mc-account-stack > .fi-section').evaluateAll(sections => sections.flatMap(section => {
        const box = section.getBoundingClientRect();
        const column = section.parentElement!.getBoundingClientRect();
        return box.left < column.left - 1 || box.right > column.right + 1 || box.right > innerWidth + 1
          ? [{ width: box.width, columnWidth: column.width, right: box.right }] : [];
      }));
      expect(overflow, `Account columns at ${width}px`).toEqual([]);
    };
    await checkColumns();
    if (width >= 1024) {
      await page.getByRole('button', { name: 'Collapse sidebar' }).click();
      await expect(page.getByRole('button', { name: 'Expand sidebar' })).toBeVisible();
      await checkColumns();
      await page.screenshot({ path: testInfo.outputPath(`account-collapsed-${width}.png`), fullPage: true, animations: 'disabled' });
      await page.getByRole('button', { name: 'Expand sidebar' }).click();
    } else {
      await page.screenshot({ path: testInfo.outputPath(`account-${width}.png`), fullPage: true, animations: 'disabled' });
    }
  }
});

test('shared section layouts fit on account, dashboard, tools and project pages', async ({ page }) => {
  test.setTimeout(90_000);
  const state = qaState();
  await login(page, state.users.owner.email, state.password);
  const routes = ['/app', '/app/account-settings', '/app/my-tasks', '/app/global-search', '/app/calendar', '/app/document-templates', '/app/feedback-forms', '/app/individual-support-calculator', '/app/public-library', '/app/notification-preferences',
    ...['', '/board', '/participants', '/mobility', '/documents', '/feedback', '/finalisation', '/edit'].map(suffix => `/app/projects/${state.projects.documents.id}${suffix}`)];
  for (const width of [1024, 390]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of routes) {
      const response = await page.goto(route);
      expect(response?.status(), route).toBe(200);
      const overflow = await page.locator('.fi-page-content .fi-section').evaluateAll(sections => sections.filter(section => section.getClientRects().length).flatMap(section => {
        const box = section.getBoundingClientRect();
        // Collaboration wrappers use display:contents and have no layout box.
        let container = section.parentElement!;
        while (getComputedStyle(container).display === 'contents' && container.parentElement) {
          container = container.parentElement;
        }
        const parent = container.getBoundingClientRect();
        return box.right > innerWidth + 1 || box.left < -1 || box.width > parent.width + 1
          ? [{ width: box.width, parentWidth: parent.width, right: box.right }] : [];
      }));
      expect(overflow, `${route} at ${width}px`).toEqual([]);
    }
  }
});
