import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { login } from './support/auth';
import { qaState } from './support/qa-state';

test.describe('Audit remediation: exports and existing project modules', () => {
  let state: ReturnType<typeof qaState>;
  test.beforeAll(() => { state = qaState(); });

  test('existing modules load without browser or server errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await login(page, state.users.owner.email, state.password);
    for (const suffix of ['', '/write', '/board', '/participants', '/documents', '/mobility', '/finalisation']) {
      const id = suffix === '/write' ? state.projects.writing_ka152.id : state.projects.documents.id;
      const response = await page.goto(`/app/projects/${id}${suffix}`);
      expect(response?.status(), response?.url()).toBe(200);
      await expect(page.getByText(/Something went wrong|Temporary platform issue/i)).toHaveCount(0);
    }
    expect(errors).toEqual([]);
  });

  test('participant search updates the register without a page reload', async ({ page }) => {
    await login(page, state.users.owner.email, state.password);
    await page.goto(`/app/projects/${state.projects.participants.id}/participants`);
    await page.getByPlaceholder(/Search name/i).fill('Mara');
    await expect(page.getByText('Mara Ionescu').first()).toBeVisible();
    await expect(page.getByText('Ana Adams')).toHaveCount(0);
    await page.getByPlaceholder(/Search name/i).fill('');
    await expect(page.getByText('Ana Adams').first()).toBeVisible();
  });

  test('application downloads contain PDF and native Word content', async ({ page }) => {
    await login(page, state.users.owner.email, state.password);
    const project = state.projects.writing_ka152;
    await page.goto(`/app/projects/${project.id}/write`);
    const answer = `Export integrity check ${Date.now()}`;
    const saved = page.waitForResponse(response => response.url().endsWith('/livewire/update') && response.status() === 200 && (response.request().postData() || '').includes(answer));
    await page.locator('textarea[placeholder^="Write your answer here"]').first().fill(answer);
    await saved;
    const pdf = await page.request.get(`/projects/${project.id}/export-application`);
    expect(pdf.status()).toBe(200);
    expect((await pdf.body()).subarray(0, 5).toString()).toBe('%PDF-');
    const word = await page.request.get(`/projects/${project.id}/export-application-word`);
    expect(word.status()).toBe(200);
    expect(word.headers()['content-type']).toContain('wordprocessingml.document');
    const directory = await mkdtemp(path.join(os.tmpdir(), 'mobilitycloud-word-'));
    try {
      const file = path.join(directory, 'application.docx');
      await writeFile(file, await word.body());
      const xml = execFileSync('unzip', ['-p', file, 'word/document.xml'], { encoding: 'utf8' });
      expect(xml).toContain(answer);
      expect(xml).toContain('Project Application');
    } finally {
      await rm(directory, { recursive: true, force: true });
    }
  });

  test('finalisation prepares, downloads and regenerates a complete archive', async ({ page }) => {
    test.setTimeout(90_000);
    await login(page, state.users.owner.email, state.password);
    await page.goto(`/app/projects/${state.projects.documents.id}/finalisation`);
    const download = page.getByRole('link', { name: /Download ZIP/i });
    const prepare = async () => {
      const previous = await download.count() ? await download.getAttribute('href') : null;
      const queued = page.waitForResponse(response => response.url().endsWith('/livewire/update') && (response.request().postData() || '').includes('requestFinalArchive'));
      await page.getByRole('button', { name: /^Prepare (archive|fresh archive|again)$/i }).click();
      await queued;
      await expect(download).toBeVisible({ timeout: 45_000 });
      if (previous) await expect(download).not.toHaveAttribute('href', previous);
    };
    await prepare();
    const firstUrl = await download.getAttribute('href');
    const archive = await page.request.get(firstUrl!);
    expect(archive.status()).toBe(200);
    expect(archive.headers()['cache-control']).toContain('private');
    const directory = await mkdtemp(path.join(os.tmpdir(), 'mobilitycloud-final-'));
    try {
      const file = path.join(directory, 'archive.zip');
      await writeFile(file, await archive.body());
      execFileSync('unzip', ['-q', file, '-d', directory]);
      const entries = execFileSync('unzip', ['-Z1', file], { encoding: 'utf8' }).split('\n');
      const index = entries.find((entry) => entry.endsWith('/INDEX.html'))!;
      expect(index).toBeTruthy();
      expect(entries.some((entry) => /generated-records\/attendance\/.*\.pdf$/.test(entry))).toBe(true);
      expect(entries.some((entry) => /generated-records\/expense-reports\/.*\.pdf$/.test(entry))).toBe(true);
      const html = await readFile(path.join(directory, index), 'utf8');
      expect(html).toContain('Data snapshot:');
      expect(html).toContain('Documents and files');
      const data = JSON.parse(await readFile(path.join(directory, index.replace('INDEX.html', '00-project-data/project-data.json')), 'utf8'));
      expect(data.format_version).toBe(3);
      expect(data.file_index.length).toBeGreaterThan(2);
    } finally {
      await rm(directory, { recursive: true, force: true });
    }
    await prepare();
    await expect(download).not.toHaveAttribute('href', firstUrl!);
  });
});
