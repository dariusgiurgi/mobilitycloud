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
- Confirm standard table blocks appear for relevant questions.
- Search within questions and answers.
- Save a draft answer, reload the page and confirm it persists.
- Add a row to a standard application table, reload the page and confirm it persists.

## Next bot scenarios to automate

### Projects

- Archive project from Settings only.
- Restore archived project from Archived projects.
- Duplicate project.
- Verify owner/editor/viewer action visibility on Overview, Settings and Documents.

### Writing

- Create a project with each supported application template.
- Verify only the selected template's questions appear.
- Export application content.
- Verify template switching preserves compatible answers and removes old official questions.
- Verify editor can write and viewer remains read-only in the Writing module.

### Budget

- Add budget baskets.
- Add expenses in EUR and a secondary currency.
- Change project currency rate and verify project totals update.
- Generate formal expenditure report.

### Participants

- Import exported CSV without date-format errors.
- Add participant manually.
- Generate attendance sheets grouped by association.
- Upload signed attendance sheets through Documents.

### Documents

- Generate civil convention.
- Generate formal expenditure report.
- Upload signed copy.
- Verify pending signature state changes.
- Verify final archive includes generated and uploaded documents in ordered folders.

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
