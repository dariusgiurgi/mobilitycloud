# MobilityCloud QA browser bot task list

This checklist is the shared source of truth for human QA and Playwright browser bots.

Run locally or on staging, not against real production data unless the scenario is explicitly marked read-only.

## Commands

```bash
php artisan qa:seed-e2e --fresh
npm run qa:e2e
npm run qa:e2e:report
```

Useful variants:

```bash
npm run qa:e2e:headed
npm run qa:e2e:ui
E2E_BASE_URL=https://staging.mobilitycloud.eu npm run qa:e2e
```

The seeder creates deterministic QA accounts and writes:

```text
storage/app/private/e2e-state.json
```

Default QA password:

```text
MobilityCloudQA!2026
```

## Current automated bot coverage

### 1. Authentication smoke

- Open `/app/login`.
- Sign in as `qa.owner@mobilitycloud.test`.
- Confirm the authenticated app shell loads.
- Confirm the dashboard is available.

### 2. Owner project creation

- Sign in as the QA owner.
- Open `/app/projects/create`.
- Create a project without selecting a template.
- Confirm the redirect opens the new project page.
- Confirm no workspace is required.

### 3. Existing-user project invitation

- Seed a pending project invitation for `qa.editor@mobilitycloud.test`.
- Sign in as the invited editor.
- Open the project invitation link.
- Confirm the invitation is accepted.
- Confirm the project appears as a shared project.
- Confirm the owner label is visible.
- Confirm the editor role is visible.

### 4. Shared projects do not count against the invited user's plan

- Accept a shared project as a Free-plan editor.
- Create one owned project from the same Free-plan account.
- Confirm creation is allowed because the shared project belongs to another account's subscription.

### 5. Free-plan owned project limit

- Sign in as a Free-plan account that already owns one project.
- Confirm the New project action is hidden.
- Open `/app/projects/create` directly.
- Confirm the account is blocked from creating another owned project.

### 6. Viewer read-only access

- Seed a pending project invitation for `qa.viewer@mobilitycloud.test`.
- Sign in as the invited viewer.
- Accept the invitation.
- Confirm the project opens.
- Confirm the owner label is visible.
- Confirm the viewer role is visible.
- Confirm the Settings project tab is not available.

### 7. Platform admin surface

- Sign in as `qa.platform-owner@mobilitycloud.test`.
- Confirm the platform administration dashboard opens.
- Confirm the bot does not land in any workspace onboarding/dashboard flow.

### 8. Writing workspace for official KA152 applications

- Seed a dedicated `QA Bot Writing KA152 Project`.
- Open the Writing module as the project owner.
- Confirm the official KA152 questions are visible and not truncated.
- Open Template manager and confirm the verified template catalog is visible.
- Search the template catalog for KA153 and confirm catalog cards are filtered.
- Confirm template audit and switch impact preview are visible.
- Confirm standard table blocks appear for relevant questions.
- Search within questions and answers.
- Save a draft answer, reload the page and confirm it persists.
- Add a row to a standard application table, reload the page and confirm it persists.
- Confirm an invited Editor can write in the shared project.
- Confirm an invited Viewer can open Writing in read-only mode.
- Confirm Viewer cannot see template/table edit actions.
- Confirm owner, editor and viewer can export the application.
- Confirm accounts without project access cannot export the application.

### 9. Budget estimator and implementation board

- Seed a dedicated active budget project with approved grant, baskets and expenses.
- Open the writing-stage grant estimator.
- Confirm estimator inputs, totals and autosave messaging are visible.
- Open the active project Budget board.
- Confirm approved budget, spent amount and seeded expenses are visible.
- Confirm the project budget PDF export works for the owner.
- Confirm accounts without project access cannot export the budget report.
- Cover add-basket, add-expense and project-rate currency conversion through feature tests.
- Cover project currency updates recalculating only that project's expenses through feature tests.

### 10. Participant register and CSV export

- Seed a dedicated participant project with multiple organisations.
- Include adult, minor and fewer-opportunity participant cases.
- Open the participant register as owner.
- Confirm participant statistics, names, minor and FO badges are visible.
- Search/filter the participant list by name.
- Export participant CSV and confirm alphabetical, Excel-safe content.
- Add a participant manually from the browser.
- Confirm minor and fewer-opportunity flags appear for the created participant.
- Edit the browser-created participant and confirm the updated name appears.
- Import a participant through the browser CSV modal.
- Confirm the imported participant and organisation appear in the register.
- Generate an attendance list from the Participants page.
- Confirm the generated attendance PDF downloads.
- Confirm the generated attendance document appears in the Documents centre.
- Confirm Viewer can open the register in read-only mode.
- Confirm Viewer cannot see import, attendance generation or add-participant actions.
- Confirm accounts without project access cannot export participants.

### 11. Documents centre and final archive

- Seed a dedicated documents project with uploaded, generated and civil convention records.
- Open the Documents centre as owner.
- Confirm readiness panels, file search and pending-signature filtering work.
- Confirm generated attendance and formal expense report records are visible.
- Confirm civil convention workflow shows agreement, signed-copy and payment evidence actions.
- Edit civil convention details through the browser modal and confirm the saved values persist.
- Confirm dissemination organisations and evidence actions are visible.
- Confirm checklist items are visible and reflect seeded file state.
- Download attendance PDF, expense report PDF, civil convention PDF and payment evidence PDF.
- Download an uploaded private file.
- Upload a signed attendance copy through the browser Documents modal.
- Confirm pending-signature filtering updates after the signed copy is uploaded.
- Download the uploaded signed attendance copy.
- Download the final project archive ZIP.
- Confirm a Viewer can open documents and download allowed files.
- Confirm a Viewer cannot see document mutation actions.

### 12. Project lifecycle controls

- Seed a dedicated active lifecycle project and a dedicated archived project.
- Confirm the project list loads without workspace context.
- Confirm the Duplicate project entry point is visible for an account that can create projects.
- Open the project Overview and confirm Settings is reachable for the owner.
- Archive the project from Settings/Edit project actions.
- Confirm the archived project disappears from the active list.
- Open Archived projects and restore the dedicated archived project.
- Confirm the restored project returns to the active project list.
- Cover duplicate execution, shared-project duplication and project-limit blocking through feature tests.

## Next bot scenarios to automate

### Projects

- Execute Duplicate project through the browser modal.
- Verify finer owner/editor/viewer action visibility on Overview and Settings.

### Writing

- Create a project with each supported application template.
- Verify template switching preserves compatible answers and removes old official questions.
- Verify exported PDF/pack includes structured table rows.

### Budget

- Execute add/edit/delete budget basket flows through the browser.
- Execute add/edit expenses in EUR and a secondary currency through the browser.
- Generate formal expenditure report from Documents.

### Documents

- Generate formal expenditure report through the browser modal.
- Upload and remove signed copies for formal expense reports through the browser modal.
- Inspect the final archive ZIP contents and confirm ordered folders/file names.

### Dissemination and mobility

- Add dissemination report per organisation.
- Upload dissemination evidence per organisation.
- Add mobility materials/documents.
- Verify final archive includes dissemination and mobility evidence.

### Account and subscription

- Verify account settings load.
- Verify plan limits are account-based.
- Verify manual owner-granted access.
- Verify suspended account experience.

### Platform admin

- Verify user list, account details and plan fields.
- Verify owner-only actions cannot be used by platform admins.
- Verify announcements create/update/archive.
- Verify audit log entries are written for sensitive actions.
- Verify no workspace management appears in the admin navigation.

## Rules for destructive bot tests

- Prefix generated data with `QA Bot`.
- Use only `@mobilitycloud.test` email addresses.
- Run destructive cleanup only through `php artisan qa:seed-e2e --fresh`.
- Never run destructive bots on production customer data.
- Prefer staging for pre-release full regression.
