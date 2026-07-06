import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

type QaUser = {
  email: string;
  name: string;
};

type QaProject = {
  id: number;
  name: string;
};

type QaInvitation = {
  email: string;
  token: string;
  url: string;
};

export type QaState = {
  base_url: string;
  password: string;
  users: {
    owner: QaUser;
    editor: QaUser;
    viewer: QaUser;
    free: QaUser;
    admin: QaUser;
  };
  projects: {
    owned: QaProject;
    collaboration: QaProject;
    viewer: QaProject;
    writing_ka152: QaProject;
    budget_active: QaProject;
    free_owned: QaProject;
  };
  invitations: {
    editor: QaInvitation;
    viewer: QaInvitation;
  };
};

export function qaState(): QaState {
  const currentDir = path.dirname(fileURLToPath(import.meta.url));
  const statePath = path.resolve(currentDir, '../../../storage/app/private/e2e-state.json');

  return JSON.parse(readFileSync(statePath, 'utf8')) as QaState;
}
