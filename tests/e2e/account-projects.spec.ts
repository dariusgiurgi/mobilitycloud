import { expect, test } from '@playwright/test';
import { login, logoutByClearingSession } from './support/auth';
import { qaState } from './support/qa-state';

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
  });

  test('viewer invitations grant read-only project access', async ({ page }) => {
    await login(page, state.users.viewer.email, state.password);
    await page.goto(new URL(state.invitations.viewer.url).pathname);

    await expect(page).toHaveURL(new RegExp(`/app/projects/${state.projects.viewer.id}`));
    await expect(page.getByText(state.projects.viewer.name).first()).toBeVisible();
    await expect(page.getByText(/Owner: QA Bot Owner/i).first()).toBeVisible();
    await expect(page.getByText(/^\s*Viewer\s*$/).last()).toBeVisible();
    await expect(page.getByRole('link', { name: /^Settings$/i })).toHaveCount(0);
  });

  test('platform owner opens the platform administration surface, not a workspace dashboard', async ({ page }) => {
    await login(page, state.users.admin.email, state.password);

    await expect(page.getByRole('heading', { name: /Platform administration/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /workspace/i })).toHaveCount(0);

    await logoutByClearingSession(page);
    await expect(page.getByLabel(/email/i)).toBeVisible();
  });
});
