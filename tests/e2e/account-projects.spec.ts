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

async function expectParticipantsCsv(page: Page, projectId: number) {
  const csv = await page.request.get(`/projects/${projectId}/export-participants`);
  expect(csv.status()).toBe(200);
  expect(csv.headers()['content-type']).toContain('text/csv');

  const body = await csv.text();
  expect(body).toContain('Last name');
  expect(body).toContain('Adams;Ana');
  expect(body).toContain('Ionescu;Mara');
  expect(body).toContain('Zimmer;Zoe');
  expect(body).toContain("'=HYPERLINK");
  expect(body.indexOf('Adams;Ana')).toBeLessThan(body.indexOf('Ionescu;Mara'));
  expect(body.indexOf('Ionescu;Mara')).toBeLessThan(body.indexOf('Zimmer;Zoe'));
}

async function expectPrivatePdf(page: Page, path: string) {
  const response = await page.request.get(path);
  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('application/pdf');
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

    await page.goto(`/app/projects/${state.projects.participants.id}/participants`);
    await expect(page.getByRole('heading', { name: /Participant register/i })).toBeVisible();
    await expect(page.getByText(state.projects.participants.name).first()).toBeVisible();
    await expect(page.getByText(/2 organisations/i)).toBeVisible();
    await expect(page.getByText(/1 participants with fewer opportunities/i)).toBeVisible();
    await expect(page.getByText('Ana Adams').first()).toBeVisible();
    await expect(page.getByText('Mara Ionescu').first()).toBeVisible();
    await expect(page.getByText('Zoe Zimmer').first()).toBeVisible();
    const minorParticipantRow = page.getByRole('row', { name: /Mara Ionescu/i });
    await expect(minorParticipantRow.getByText('MINOR')).toBeVisible();
    await expect(minorParticipantRow.getByText('FO')).toBeVisible();

    await page.getByPlaceholder(/Search name/i).fill('Mara');
    await expect(page.getByText('Mara Ionescu').first()).toBeVisible();
    await expect(page.getByText('Ana Adams')).toHaveCount(0);
    await page.getByPlaceholder(/Search name/i).fill('');

    await expectParticipantsCsv(page, state.projects.participants.id);

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

    const forbiddenParticipantsExport = await page.request.get(`/projects/${state.projects.participants.id}/export-participants`);
    expect(forbiddenParticipantsExport.status()).toBe(403);
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

    await page.goto(`/app/projects/${state.projects.viewer.id}/participants`);
    await expect(page.getByRole('heading', { name: /Participant register/i })).toBeVisible();
    await expect(page.getByText(/Read-only access/i)).toBeVisible();
    await expect(page.getByText('Mara Ionescu').first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Import CSV/i })).toHaveCount(0);
    await expect(page.getByRole('button', { name: /Attendance list/i })).toHaveCount(0);
    await expect(page.getByRole('button', { name: /Add participant/i })).toHaveCount(0);
    await expectParticipantsCsv(page, state.projects.viewer.id);

    const documentsProject = state.projects.documents;
    await page.goto(`/app/projects/${documentsProject.id}/documents`);
    await expect(page.getByText(documentsProject.name).first()).toBeVisible();
    await expect(page.getByText('QA Bot attendance list - Mobility workshop').first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Add document/i })).toHaveCount(0);
    await expect(page.getByRole('button', { name: /Upload signed copy/i })).toHaveCount(0);
    await expectPrivatePdf(page, `/projects/${documentsProject.id}/documents/${documentsProject.attendance_document_id}/attendance`);
  });

  test('documents centre covers generated records, civil conventions and read-only sharing', async ({ page }) => {
    const project = state.projects.documents;

    await login(page, state.users.owner.email, state.password);
    await page.goto(`/app/projects/${project.id}/documents`);

    await expect(page.getByRole('heading', { name: /Project document centre/i })).toBeVisible();
    await expect(page.getByText(/Project readiness/i)).toBeVisible();
    await expect(page.getByText(/Document readiness/i)).toBeVisible();
    await expect(page.getByText(project.name).first()).toBeVisible();
    await expect(page.getByText('QA Bot uploaded grant agreement').first()).toBeVisible();
    await expect(page.getByText('QA Bot attendance list - Mobility workshop').first()).toBeVisible();
    await expect(page.getByText('QA Bot official expense report').first()).toBeVisible();
    await expect(page.getByText(/awaiting signature/i).first()).toBeVisible();

    await page.getByPlaceholder(/Search documents/i).fill('grant');
    await expect(page.getByText('QA Bot uploaded grant agreement').first()).toBeVisible();
    await expect(page.getByText('QA Bot attendance list - Mobility workshop')).toHaveCount(0);
    await page.getByPlaceholder(/Search documents/i).fill('');

    await page.getByRole('button', { name: /View pending signatures/i }).first().click();
    await expect(page.getByText('QA Bot attendance list - Mobility workshop').first()).toBeVisible();
    await expect(page.getByText('QA Bot official expense report').first()).toBeVisible();
    await expect(page.getByText('QA Bot uploaded grant agreement')).toHaveCount(0);

    await page.getByRole('tab', { name: /Civil conventions/i }).click();
    await expect(page.getByText('QA Bot facilitation civil convention').first()).toBeVisible();
    await page.getByText('QA Bot facilitation civil convention').first().click();
    await expect(page.getByText(/Download agreement/i).first()).toBeVisible();
    await expect(page.getByText(/Upload signed agreement/i).first()).toBeVisible();
    await expect(page.getByText(/Download payment evidence/i).first()).toBeVisible();

    await page.getByRole('tab', { name: /Dissemination/i }).click();
    await expect(page.getByText('Scoala de Jocuri').first()).toBeVisible();
    await expect(page.getByText('Youth Group Spain').first()).toBeVisible();
    await expect(page.getByText(/Save report/i).first()).toBeVisible();
    await expect(page.getByText(/Upload evidence/i).first()).toBeVisible();

    await page.getByRole('tab', { name: /Checklist/i }).click();
    await expect(page.getByText(/Project file checklist/i)).toBeVisible();
    await expect(page.getByText(/Grant agreement/i).first()).toBeVisible();

    await expectPrivatePdf(page, `/projects/${project.id}/documents/${project.attendance_document_id}/attendance`);
    await expectPrivatePdf(page, `/projects/${project.id}/documents/${project.expense_report_document_id}/expense-report`);
    await expectPrivatePdf(page, `/projects/${project.id}/expenses/${project.civil_expense_id}/civil-convention`);
    await expectPrivatePdf(page, `/projects/${project.id}/expenses/${project.civil_expense_id}/payment-statement`);

    const uploaded = await page.request.get(`/projects/${project.id}/documents/${project.uploaded_document_id}/file`);
    expect(uploaded.status()).toBe(200);

    const archive = await page.request.get(`/projects/${project.id}/final-archive`);
    expect(archive.status()).toBe(200);
    expect(archive.headers()['content-type']).toContain('zip');
  });

  test('platform owner opens the platform administration surface, not a workspace dashboard', async ({ page }) => {
    await login(page, state.users.admin.email, state.password);

    await expect(page.getByRole('heading', { name: /Platform administration/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /workspace/i })).toHaveCount(0);

    await logoutByClearingSession(page);
    await expect(page.getByLabel(/email/i)).toBeVisible();
  });
});
