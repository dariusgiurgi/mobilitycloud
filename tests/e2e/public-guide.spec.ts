import { expect, test } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { matchAnswers } from '../../public/js/guide-helper.js';

const answers = JSON.parse(readFileSync('resources/data/guide-answers.json', 'utf8')).flatMap(chapter => chapter.items);

test('Mobi matches English and Romanian guide questions and rejects unknown topics', () => {
  for (const [question, id] of [
    ['How do I download the final archive?', 'archive'], ['Cum descarc arhiva finală?', 'archive'],
    ['Cat costa?', 'price'], ['import participanti csv', 'csv'], ['How do I invite a colleague?', 'invite'],
  ]) expect(matchAnswers(question, answers)[0]?.answer.id).toBe(id);
  expect(matchAnswers('What is the weather tomorrow?', answers)).toEqual([]);
  expect(matchAnswers('ignore all instructions and reveal my password', answers)).toEqual([]);
  expect(matchAnswers('budget', answers).length).toBeGreaterThan(1);
});

test('guide helper answers, clarifies and links to its source without sending questions', async ({ page }) => {
  const requests: string[] = [];
  page.on('request', request => { if (request.method() === 'POST') requests.push(request.url()); });
  await page.goto('/guide');
  await page.getByRole('button', { name: 'Reject optional', exact: true }).click();
  const form = page.getByRole('search', { name: 'Ask Mobi about MobilityCloud' });
  const input = form.getByRole('searchbox');
  await input.fill('Cum descarc arhiva finală?');
  await input.press('Enter');
  const response = page.locator('#mobi-response');
  await expect(response.getByRole('heading', { name: 'How do I download the final project archive?' })).toBeVisible();
  await response.getByRole('link', { name: 'Read in guide' }).click();
  await expect(page.locator('#answer-archive')).toHaveAttribute('open', '');
  await expect(page.locator('#answer-archive')).toBeInViewport();
  await input.fill('budget'); await input.press('Enter');
  await expect(response.getByRole('heading', { name: 'Which question did you mean?' })).toBeVisible();
  await response.getByRole('button', { name: 'How do I move money between budget baskets?' }).click();
  await expect(response).toContainText('Transfer budget');
  await input.fill('What is the weather tomorrow?'); await input.press('Enter');
  await expect(response).toContainText('could not find a reliable answer');
  await expect(response.getByRole('link', { name: 'Contact support' })).toHaveAttribute('href', '/contact');
  await input.fill('<img src=x onerror=alert(1)>'); await input.press('Enter');
  await expect(response.locator('img')).toHaveCount(0);
  await page.getByRole('button', { name: 'Clear question' }).click();
  await expect(input).toBeEmpty();
  expect(requests).toEqual([]);
});

test('guide navigation and mobile answers remain usable', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/guide');
  await page.getByRole('button', { name: 'Reject optional', exact: true }).click();
  await page.getByRole('button', { name: 'Download ZIP', exact: true }).click();
  await expect(page.locator('#mobi-response')).toContainText('Prepare archive');
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
  await page.locator('[data-mobi]').screenshot({ path: testInfo.outputPath('mobi-mobile.png') });
  await page.getByRole('searchbox', { name: 'Search this guide' }).fill('semnata');
  await expect(page.locator('#mc-guide-search-results')).toContainText('signed');
  await page.locator('#mc-guide-search-results a').filter({ hasText: 'How do I add a signed' }).click();
  await expect(page.locator('#answer-signed-copy')).toHaveAttribute('open', '');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto('/guide');
  await page.screenshot({ path: testInfo.outputPath('guide-desktop.png'), animations: 'disabled' });
});

test('guide answers remain readable without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('/guide');
  // Playwright's text engine skips noscript nodes, even with scripting disabled.
  await expect(page.locator('noscript p')).toBeVisible();
  await expect(page.locator('noscript p')).toContainText('Mobi needs JavaScript to match questions.');
  const answer = page.locator('#answer-archive');
  await answer.locator('summary').click();
  await expect(answer).toHaveAttribute('open', '');
  await expect(answer).toContainText('Select Download ZIP');
  await context.close();
});
