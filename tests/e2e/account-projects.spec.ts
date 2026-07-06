import { expect, test, type Page } from '@playwright/test';
import { login, logoutByClearingSession } from './support/auth';
import { qaState } from './support/qa-state';

async function expectApplicationExports(page: Page, projectId: number, expectedText: string) {
  const pdf = await page.request.get(`/projects/${projectId}/export-application`);
  expect(pdf.status()).toBe(200);
  expect(pdf.headers()['content-type']).toContain('application/pdf');

  const word = await page.request.get(`/projects/${projectId}/export-application-word`);
  expect(word.status()).toBe(200);
  expect(word.headers()['content-type']).toContain('application/msword');
  expect(await word.text()).toContain(expectedText);

  const pack = await page.request.get(`/projects/${projectId}/export-application-pack`);
  expect(pack.status()).toBe(200);
  expect(pack.headers()['content-type']).toContain('application/pdf');
}

test.describe.serial('Account-owned projects and project invitations', () => {
  const state = qaState();

  test('owner can sign in, see dashboard projects, open core modules and create a project without a workspace', async ({ page }) => {
    await login(page, state.users.owner.email, state.password);

    await expect(page.getByRole('heading', { name: /Project dashboard|Platform overview/i })).toBeVisible();
    await expect(page.getByText(state.projects.owned.name).first()).toBeVisible();

    const projectBase = `/app/projects/${state.projects.owned.id}`;
    const modules = [
      projectBase,
      `${projectBase}/write`,
      `${projectBase}/estimate`,
      `${projectBase}/mobility`,
      `${projectBase}/participants`,
      `${projectBase}/documents`,
      `${projectBase}/edit`,
    ];

    for (const path of modules) {
      await page.goto(path);
      await expect(page).toHaveURL(new RegExp(path.replace(/\//g, '\\/')));
      await expect(page.getByText(/Something went wrong|Temporary platform issue|This page is not available/i)).toHaveCount(0);
      await expect(page.getByText(state.projects.owned.name).first()).toBeVisible();
    }

    await page.goto(`/app/projects/${state.projects.writing_ka152.id}/write`);
    await expect(page.getByRole('heading', { name: /Application workspace/i })).toBeVisible();
    await expect(page.getByText(state.projects.writing_ka152.name).first()).toBeVisible();
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveValue(/What do you want to achieve by implementing the project/i);

    await page.getByPlaceholder(/Search questions or answers/i).fill('background of the participants');
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveValue(/Please describe the background of the participants in each participating group/i);
    await expect(page.getByText('Participant groups').first()).toBeVisible();

    await page.getByPlaceholder(/Search questions or answers/i).fill('select up to three topics');
    await expect(page.getByText('Project topics').first()).toBeVisible();

    await page.getByPlaceholder(/Search questions or answers/i).fill('fewer opportunities');
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveValue(/participants involved in the activities who face situations/i);
    await page.getByPlaceholder(/Search questions or answers/i).fill('');
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveValue(/What do you want to achieve by implementing the project/i);

    const qaAnswer = `QA browser bot saved this KA152 answer at ${Date.now()}.`;
    await page.locator('textarea[placeholder^="Write your answer here"]').first().fill(qaAnswer);
    await page.waitForTimeout(1_200);
    await page.reload();
    await expect(page.locator('textarea[placeholder^="Write your answer here"]').first()).toHaveValue(qaAnswer);

    await page.getByPlaceholder(/Search questions or answers/i).fill('background of the participants');
    const participantTable = page.locator('.mc-wa-table-block').filter({ hasText: 'Participant groups' }).first();
    await expect(participantTable).toBeVisible();
    await participantTable.getByRole('button', { name: /Add row/i }).click();
    await participantTable.getByPlaceholder('Group / country').fill('Romania group');
    await participantTable.getByPlaceholder('Participants').fill('12 young people');
    await page.waitForTimeout(1_200);
    await page.reload();
    await expect(page.getByPlaceholder('Group / country').first()).toHaveValue('Romania group');
    await expect(page.getByPlaceholder('Participants').first()).toHaveValue('12 young people');
    await expectApplicationExports(page, state.projects.writing_ka152.id, qaAnswer);

    await page.goto(`/app/projects/${state.projects.owned.id}/estimate`);
    await expect(page.getByRole('heading', { name: /Grant estimator/i })).toBeVisible();
    await expect(page.getByText(/Writing stage/i).first()).toBeVisible();
    await expect(page.getByText(/Total grant/i)).toBeVisible();
    await expect(page.getByText(/Changes are saved automatically/i)).toBeVisible();

    await page.goto(`/app/projects/${state.projects.budget_active.id}/board`);
    await expect(page.getByRole('heading', { name: /Budget control/i })).toBeVisible();
    await expect(page.getByText(state.projects.budget_active.name).first()).toBeVisible();
    await expect(page.getByText(/2 expenses/i)).toBeVisible();
    await expect(page.getByPlaceholder('Expense name…').nth(0)).toHaveValue('QA Bot train tickets');
    await expect(page.getByPlaceholder('Expense name…').nth(1)).toHaveValue('QA Bot facilitation materials');
    await expect(page.getByText(/€ 5,000.00/).first()).toBeVisible();
    await expect(page.getByText(/€ 350.00/).first()).toBeVisible();

    const budgetPdf = await page.request.get(`/projects/${state.projects.budget_active.id}/export`);
    expect(budgetPdf.status()).toBe(200);
    expect(budgetPdf.headers()['content-type']).toContain('application/pdf');

    const projectName = `QA Bot Created ${Date.now()}`;
    await page.goto('/app/projects/create');

    await expect(page.getByText(/Create a new project/i)).toBeVisible();
    await page.getByLabel(/Project name/i).fill(projectName);
    await page.getByRole('button', { name: /^Create$/i }).click();

    await expect(page).toHaveURL(/\/app\/projects\/\d+/);
    await expect(page.getByText(projectName).first()).toBeVisible();
  });

  test('existing editor accepts an invitation, sees the shared owner label and can still create one owned project', async ({ page }) => {
    await login(page, state.users.editor.email, state.password);
    await page.goto(new URL(state.invitations.editor.url).pathname);

    await expect(page).toHaveURL(new RegExp(`/app/projects/${state.projects.collaboration.id}`));
    await expect(page.getByText(state.projects.collaboration.name).first()).toBeVisible();
    await expect(page.getByText(/Owner: QA Bot Owner/i).first()).toBeVisible();
    await expect(page.getByText(/^\s*Editor\s*$/).last()).toBeVisible();

    await page.goto(`/app/projects/${state.projects.collaboration.id}/write`);
    await expect(page.getByRole('heading', { name: /Application workspace/i })).toBeVisible();
    await expect(page.getByText(/Read-only access/i)).toHaveCount(0);
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveValue(/What do you want to achieve by implementing the project/i);

    const editorAnswer = `QA editor saved this shared project answer at ${Date.now()}.`;
    await page.locator('textarea[placeholder^="Write your answer here"]').first().fill(editorAnswer);
    await page.waitForTimeout(1_200);
    await page.reload();
    await expect(page.locator('textarea[placeholder^="Write your answer here"]').first()).toHaveValue(editorAnswer);
    await expectApplicationExports(page, state.projects.collaboration.id, editorAnswer);

    const projectName = `QA Bot Editor Owned ${Date.now()}`;
    await page.goto('/app/projects/create');

    await expect(page.getByText(/Create a new project/i)).toBeVisible();
    await page.getByLabel(/Project name/i).fill(projectName);
    await page.getByRole('button', { name: /^Create$/i }).click();

    await expect(page).toHaveURL(/\/app\/projects\/\d+/);
    await expect(page.getByText(projectName).first()).toBeVisible();
  });

  test('free accounts are blocked only after their own project limit is reached', async ({ page }) => {
    await login(page, state.users.free.email, state.password);
    await page.goto('/app/projects');

    await expect(page.getByText(state.projects.free_owned.name).first()).toBeVisible();
    await expect(page.getByRole('link', { name: /New project/i })).toHaveCount(0);

    await page.goto('/app/projects/create');
    await expect(page.getByText(/403|forbidden|not authorized|not available/i)).toBeVisible();

    const forbiddenExport = await page.request.get(`/projects/${state.projects.viewer.id}/export-application-word`);
    expect(forbiddenExport.status()).toBe(403);

    const forbiddenBudgetExport = await page.request.get(`/projects/${state.projects.budget_active.id}/export`);
    expect(forbiddenBudgetExport.status()).toBe(403);
  });

  test('viewer invitations grant read-only project access', async ({ page }) => {
    await login(page, state.users.viewer.email, state.password);
    await page.goto(new URL(state.invitations.viewer.url).pathname);

    await expect(page).toHaveURL(new RegExp(`/app/projects/${state.projects.viewer.id}`));
    await expect(page.getByText(state.projects.viewer.name).first()).toBeVisible();
    await expect(page.getByText(/Owner: QA Bot Owner/i).first()).toBeVisible();
    await expect(page.getByText(/^\s*Viewer\s*$/).last()).toBeVisible();
    await expect(page.getByRole('link', { name: /^Settings$/i })).toHaveCount(0);

    await page.goto(`/app/projects/${state.projects.viewer.id}/write`);
    await expect(page.getByRole('heading', { name: /Application workspace/i })).toBeVisible();
    await expect(page.getByText(/Read-only access/i)).toBeVisible();
    await expect(page.locator('textarea[placeholder^="Write your answer here"]').first()).toHaveAttribute('readonly', '');
    await expect(page.locator('textarea:not([placeholder])').first()).toHaveAttribute('readonly', '');
    await expect(page.getByRole('button', { name: /Template manager/i })).toHaveCount(0);
    await expect(page.getByRole('button', { name: /Add row/i })).toHaveCount(0);
    await expectApplicationExports(page, state.projects.viewer.id, 'QA bot baseline answer for the official KA152 objectives question.');
  });

  test('platform owner opens the platform administration surface, not a workspace dashboard', async ({ page }) => {
    await login(page, state.users.admin.email, state.password);

    await expect(page.getByRole('heading', { name: /Platform administration/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /workspace/i })).toHaveCount(0);

    await logoutByClearingSession(page);
    await expect(page.getByLabel(/email/i)).toBeVisible();
  });
});
