import { expect, test, type Page } from '@playwright/test';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
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

const MINIMAL_SIGNED_PDF = `%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>
endobj
xref
0 4
0000000000 65535 f
0000000010 00000 n
0000000060 00000 n
0000000117 00000 n
trailer
<< /Root 1 0 R /Size 4 >>
startxref
190
%%EOF`;

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

    await page.getByRole('button', { name: /Template manager/i }).click();
    await expect(page.getByText(/Application template manager/i)).toBeVisible();
    await expect(page.getByText('Template catalog', { exact: true })).toBeVisible();
    await expect(page.getByText('Officially verified', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Switch impact preview', { exact: true })).toBeVisible();
    await page.getByPlaceholder(/Search by KA code, sector, form or keyword/i).fill('153');
    await expect(page.locator('.mc-template-card').filter({ hasText: 'KA153-YOU' })).toHaveCount(1);
    await expect(page.locator('.mc-template-card').filter({ hasText: 'KA152-YOU' })).toHaveCount(0);
    await expect(page.locator('.mc-template-card').filter({ hasText: /31 questions/i })).toHaveCount(1);
    await page.locator('.mc-modal-panel-wide .mc-iconbtn').first().click();
    await expect(page.getByText(/Application template manager/i)).toHaveCount(0);

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

    const manualParticipantLastName = `Browser${Date.now()}`;
    await page.getByRole('button', { name: /^Add participant$/i }).click();
    await expect(page.getByText(/^Add participant$/i)).toBeVisible();
    await page.getByLabel(/Participant first name/i).fill('QA');
    await page.getByLabel(/Participant last name/i).fill(manualParticipantLastName);
    await page.getByLabel(/Participant birth date/i).fill('2010-05-20');
    await page.getByLabel(/Participant nationality/i).fill('Romanian');
    await page.getByLabel(/Participant country/i).fill('Romania');
    await page.getByLabel(/Participant email/i).fill(`qa.${manualParticipantLastName.toLowerCase()}@mobilitycloud.test`);
    await page.getByLabel(/Participant fewer opportunities/i).check();
    await page.getByRole('button', { name: /^Save$/i }).click();
    await expect(page.getByText(`QA ${manualParticipantLastName}`).first()).toBeVisible();

    const manualParticipantRow = page.getByRole('row', { name: new RegExp(`QA ${manualParticipantLastName}`) });
    await expect(manualParticipantRow.getByText('MINOR')).toBeVisible();
    await expect(manualParticipantRow.getByText('FO')).toBeVisible();
    await manualParticipantRow.getByRole('button', { name: new RegExp(`Edit QA ${manualParticipantLastName}`) }).click();
    await expect(page.getByText(/^Edit participant$/i)).toBeVisible();
    await page.getByLabel(/Participant last name/i).fill(`${manualParticipantLastName} Edited`);
    await page.getByRole('button', { name: /^Save$/i }).click();
    await expect(page.getByText(`QA ${manualParticipantLastName} Edited`).first()).toBeVisible();

    await expectParticipantsCsv(page, state.projects.participants.id);

    const csvImportDir = await mkdtemp(path.join(os.tmpdir(), 'mobilitycloud-participants-'));
    const csvImportPath = path.join(csvImportDir, 'participants-import.csv');
    const importedParticipantLastName = `Imported${Date.now()}`;
    await writeFile(csvImportPath, [
      '"Last name";"First name";Organisation;Role;Country;"Birth date";Nationality;Gender;Email;"Fewer opportunities"',
      `${importedParticipantLastName};CSV;QA CSV Association;Participant;Romania;2001-02-03;Romanian;female;csv.${importedParticipantLastName.toLowerCase()}@mobilitycloud.test;No`,
    ].join('\n'));

    await page.getByRole('button', { name: /^Import CSV$/i }).click();
    await expect(page.getByText(/Import participants/i)).toBeVisible();
    await page.getByLabel(/Participants CSV file/i).setInputFiles(csvImportPath);
    await page.getByRole('button', { name: /^Import$/i }).click();
    await expect(page.getByText(`CSV ${importedParticipantLastName}`).first()).toBeVisible();
    await expect(page.getByText('QA CSV Association').first()).toBeVisible();
    await rm(csvImportDir, { recursive: true, force: true });

    const attendanceActivity = `QA Bot Browser Attendance ${Date.now()}`;
    await page.getByRole('button', { name: /^Attendance list$/i }).click();
    await expect(page.getByText(/Generate attendance list/i)).toBeVisible();
    await page.getByLabel(/Attendance activity/i).fill(attendanceActivity);
    await page.getByLabel(/Attendance date/i).fill('2026-08-12');
    await page.getByLabel(/Attendance location/i).fill('Cluj-Napoca');
    const attendanceDownload = page.waitForEvent('download');
    await page.getByRole('button', { name: /^Generate PDF$/i }).click();
    const downloadedAttendance = await attendanceDownload;
    expect(downloadedAttendance.suggestedFilename()).toContain('attendance_');

    await page.goto(`/app/projects/${state.projects.participants.id}/documents`);
    await expect(page.getByText(`Attendance list - ${attendanceActivity}`).first()).toBeVisible();
    await expect(page.getByText(/Cluj-Napoca/i).first()).toBeVisible();

    const projectName = `QA Bot Created ${Date.now()}`;
    await page.goto('/app/projects/create');

    await expect(page.getByText(/Create a new project/i)).toBeVisible();
    await page.getByLabel(/Project name/i).fill(projectName);
    await page.getByRole('button', { name: /^Create$/i }).click();

    await expect(page).toHaveURL(/\/app\/projects\/\d+/);
    await expect(page.getByText(projectName).first()).toBeVisible();
  });

  test('project lifecycle actions cover duplicate, archive and restore without workspace context', async ({ page }) => {
    await login(page, state.users.owner.email, state.password);
    await page.goto('/app/projects');

    await expect(page.getByText(state.projects.lifecycle.name).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Duplicate project/i })).toBeVisible();

    await page.goto(`/app/projects/${state.projects.lifecycle.id}`);
    await expect(page.getByRole('tab', { name: /^Settings$/i })).toBeVisible();

    await page.goto(`/app/projects/${state.projects.lifecycle.id}/edit`);
    await expect(page.getByRole('button', { name: /More actions/i })).toBeVisible();
    await page.getByRole('button', { name: /More actions/i }).click();
    await page.getByText(/Archive project/i).click();
    await Promise.all([
      page.waitForURL(/\/app\/projects$/),
      page.getByRole('dialog').getByRole('button', { name: /^Delete$/i }).click(),
    ]);
    await expect(page.getByText(state.projects.lifecycle.name)).toHaveCount(0);

    await page.goto('/app/projects?archived=1');
    const archivedProjectCard = page
      .locator('.mc-project-list-card')
      .filter({ hasText: state.projects.archived.name })
      .first();
    await expect(archivedProjectCard).toBeVisible();
    page.once('dialog', dialog => dialog.accept());
    await archivedProjectCard.getByRole('button', { name: /Restore/i }).click();
    await expect(
      page.locator('.mc-project-list-card').filter({ hasText: state.projects.archived.name })
    ).toHaveCount(0);

    await page.goto('/app/projects');
    await expect(page.getByText(state.projects.archived.name).first()).toBeVisible();
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
    const civilConventionSummary = page
      .locator('summary')
      .filter({ hasText: 'QA Bot facilitation civil convention' })
      .first();
    await civilConventionSummary.click();
    await expect(page.getByText(/Download agreement/i).first()).toBeVisible();
    await expect(page.getByText(/Upload signed agreement/i).first()).toBeVisible();
    await expect(page.getByText(/Download payment evidence/i).first()).toBeVisible();
    await page.getByRole('button', { name: /Edit details/i }).click();
    await expect(page.getByRole('heading', { name: /Civil convention details/i })).toBeVisible();
    await page.getByLabel(/Civil convention number/i).fill('QA-CC-001-BOT');
    await page.getByLabel(/Provider full name/i).fill('Alex Facilitator Updated');
    await page.getByLabel(/Service description/i).fill('Updated QA facilitation services during the browser automation check.');
    await page.getByLabel(/Payment reference/i).fill('QA-PAY-BROWSER');
    await page.getByRole('button', { name: /Save details/i }).click();
    await expect(page.getByText(/Civil convention details saved/i)).toBeVisible();
    await civilConventionSummary.click();
    await page.getByRole('button', { name: /Edit details/i }).click();
    await expect(page.getByLabel(/Civil convention number/i)).toHaveValue('QA-CC-001-BOT');
    await expect(page.getByLabel(/Provider full name/i)).toHaveValue('Alex Facilitator Updated');
    await expect(page.getByLabel(/Service description/i)).toHaveValue('Updated QA facilitation services during the browser automation check.');
    await expect(page.getByLabel(/Payment reference/i)).toHaveValue('QA-PAY-BROWSER');
    await page.getByRole('button', { name: /^Cancel$/i }).click();

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

    const signedDirectory = await mkdtemp(path.join(os.tmpdir(), 'mobilitycloud-signed-'));
    const signedCopyPath = path.join(signedDirectory, 'signed-attendance.pdf');

    try {
      await writeFile(signedCopyPath, MINIMAL_SIGNED_PDF);

      await page.getByRole('tab', { name: /Files/i }).click();
      await page.getByRole('button', { name: /View pending signatures/i }).first().click();

      const attendanceDocument = page
        .locator('div[style*="padding:1rem 1.1rem"]')
        .filter({ hasText: 'QA Bot attendance list - Mobility workshop' })
        .first();

      await expect(attendanceDocument).toBeVisible();
      await attendanceDocument.getByRole('button', { name: /Upload signed copy/i }).click();
      const livewireUpload = page.waitForResponse((response) =>
        response.url().includes('/livewire/upload-file') && response.status() < 400
      );
      await page.locator('input[type="file"][wire\\:model="signedUpload"]').setInputFiles(signedCopyPath);
      await livewireUpload;
      await page.getByRole('button', { name: /^Upload$/i }).click();
      await expect(page.getByText(/Signed copy uploaded/i)).toBeVisible();
      await expect(attendanceDocument).toHaveCount(0);

      await page.getByRole('combobox').first().selectOption('all');
      await expect(attendanceDocument).toBeVisible();
      await expect(attendanceDocument).toContainText('Signed');
      await expectPrivatePdf(page, `/projects/${project.id}/documents/${project.attendance_document_id}/signed`);
    } finally {
      await rm(signedDirectory, { recursive: true, force: true });
    }

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
