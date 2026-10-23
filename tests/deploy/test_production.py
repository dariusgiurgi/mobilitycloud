"""Exercise production deploy ordering and rollback in an isolated fake host.
External programs are stubs; no SSH, real services, credentials or database are used.
"""
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
STUB = r'''#!/usr/bin/env python3
import os, sys, shutil
from pathlib import Path
name = Path(sys.argv[0]).name
args = sys.argv[1:]
root = Path(os.environ['FAKE_HOST'])
fail = os.environ.get('FAIL_STAGE', '')
with (root / 'commands.log').open('a') as log:
    log.write(name + ' ' + ' '.join(args) + '\n')
if name == 'runuser':
    args = args[args.index('--') + 1:]
    os.execvp(args[0], args)
if name == 'git':
    if args[0] == '-C': args = args[2:]
    if args[0] == 'rev-parse': print(os.environ['EXPECTED_SHA'])
    elif args[0] == 'clone':
        release = Path(args[-1])
        release.mkdir()
        for d in ['storage/framework', 'bootstrap/cache', 'deploy']:
            (release / d).mkdir(parents=True, exist_ok=True)
        (release / 'artisan').touch()
        (release / 'deploy/mobilitycloud-exports.conf').write_text('fake export worker')
    sys.exit(0)
if name == 'install':
    paths = []
    i = 0
    while i < len(args):
        if args[i] in ['-m', '-o', '-g']: i += 2; continue
        if not args[i].startswith('-'): paths.append(args[i])
        i += 1
    if '-d' in args:
        for p in paths: Path(p).mkdir(parents=True, exist_ok=True)
    else:
        Path(paths[-1]).parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(paths[0], paths[-1])
    sys.exit(0)
if name == 'mv':
    if args[0] == '-Tf': os.replace(args[1], args[2])
    else: shutil.move(args[0], args[1])
    sys.exit(0)
if name == 'composer' and fail == 'build': sys.exit(1)
if name == 'curl' and fail == 'health': sys.exit(1)
if name == 'curl' and '--write-out' in args: print('500' if fail == 'login' else '200')
if name == 'supervisorctl': print('mobilitycloud-worker RUNNING')
if name == 'php':
    command = args[1]
    cwd = Path.cwd()
    marker = cwd / 'storage/framework/down'
    if command == 'down': marker.touch()
    if command == 'up': marker.unlink(missing_ok=True)
    if command == 'migrate:status': print('Pending' if fail == 'migration' else 'Ran')
    if command == 'migrate' and fail == 'migration': sys.exit(1)
    if command == 'optimize' and fail == 'optimize' and '-aaaaaaaaaaaa' in str(cwd): sys.exit(1)
sys.exit(0)
'''


class DeploymentTests(unittest.TestCase):
    def run_deploy(self, failure=''):
        sandbox = tempfile.TemporaryDirectory(prefix='mobilitycloud-deploy-test-')
        self.addCleanup(sandbox.cleanup)
        root = Path(sandbox.name)
        app = root / 'www/mobilitycloud'
        (app / 'storage/framework').mkdir(parents=True)
        (app / '.env').write_text('TEST_ONLY=true\n')
        (app / 'untracked-operations.txt').write_text('preserve me')
        (app / 'artisan').touch()
        (root / 'lock').mkdir()
        bin_dir = root / 'bin'
        bin_dir.mkdir()
        stub = bin_dir / 'stub'
        stub.write_text(STUB)
        stub.chmod(0o755)
        for command in ['runuser', 'git', 'install', 'mv', 'composer', 'npm', 'php', 'curl', 'supervisorctl', 'systemctl', 'chown', 'chgrp', 'setfacl', 'flock']:
            (bin_dir / command).symlink_to(stub)
        script = (ROOT / 'deploy/production.sh').read_text()
        script = script.replace('/var/www', str(root / 'www')).replace('/var/lock', str(root / 'lock'))
        script = script.replace('/etc/supervisor/conf.d', str(root / 'supervisor'))
        target = root / 'deploy.sh'
        target.write_text(script)
        env = {**os.environ, 'PATH': str(bin_dir) + os.pathsep + os.environ['PATH'], 'FAKE_HOST': str(root),
               'EXPECTED_SHA': 'a' * 40, 'FAIL_STAGE': failure, 'ALLOW_ADDITIVE_MIGRATIONS': '1'}
        result = subprocess.run(['bash', str(target)], env=env, capture_output=True, text=True)
        return app, root, result

    def test_success_keeps_shared_data_and_original_untracked_files(self):
        app, root, result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertTrue(app.is_symlink())
        self.assertIn('-aaaaaaaaaaaa', str(app.resolve()))
        self.assertEqual((app / '.env').resolve(), (root / 'www/mobilitycloud-shared/.env').resolve())
        self.assertFalse((app / 'storage/framework/down').exists())
        self.assertEqual(len(list((root / 'www/mobilitycloud-releases').glob('legacy-*/untracked-operations.txt'))), 1)

    def test_build_failure_never_enters_maintenance(self):
        app, root, result = self.run_deploy('build')
        self.assertNotEqual(result.returncode, 0)
        self.assertFalse(app.is_symlink())
        self.assertFalse((app / 'storage/framework/down').exists())
        self.assertNotIn('artisan down', (root / 'commands.log').read_text())

    def test_failed_login_check_restores_old_release(self):
        app, _, result = self.run_deploy('login')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('legacy-', str(app.resolve()))
        self.assertFalse((app / 'storage/framework/down').exists())

    def test_failed_health_check_restores_old_release(self):
        app, _, result = self.run_deploy('health')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('legacy-', str(app.resolve()))
        self.assertTrue((app / 'untracked-operations.txt').exists())
        self.assertFalse((app / 'storage/framework/down').exists())

    def test_failed_optimization_restores_old_application(self):
        app, _, result = self.run_deploy('optimize')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('legacy-', str(app.resolve()))
        self.assertFalse((app / 'storage/framework/down').exists())

    def test_failed_migration_keeps_maintenance_enabled(self):
        app, _, result = self.run_deploy('migration')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('legacy-', str(app.resolve()))
        self.assertTrue((app / 'storage/framework/down').exists())


if __name__ == '__main__':
    unittest.main()
