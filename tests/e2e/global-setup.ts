import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export default async function globalSetup() {
  if (process.env.E2E_SKIP_SEED === '1') {
    return;
  }

  const currentDir = path.dirname(fileURLToPath(import.meta.url));

  execFileSync('php', ['artisan', 'qa:seed-e2e', '--fresh'], {
    cwd: path.resolve(currentDir, '../..'),
    stdio: 'inherit',
    env: {
      ...process.env,
      APP_URL: process.env.E2E_BASE_URL ?? process.env.PLAYWRIGHT_BASE_URL ?? process.env.APP_URL,
    },
  });

  const statePath = path.resolve(currentDir, '../../storage/app/private/e2e-state.json');

  if (! existsSync(statePath)) {
    throw new Error(`QA state file was not created at ${statePath}`);
  }
}
