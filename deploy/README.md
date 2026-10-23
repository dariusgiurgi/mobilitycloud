# Production rollout and rollback

This release adds no migrations and does not alter the production database schema. An existing migration now names its feedback index explicitly within MariaDB’s 64-character limit, allowing a fresh database to be initialized. Deploy only the revision that passes the Quality gate (PHP 8.3, SQLite, MariaDB, frontend build and browser downloads).

## Application release

`production.sh` builds Composer and Vite assets in a separate release directory while the current site remains online. It verifies the exact main SHA, takes a database backup, enters maintenance, drains the application workers and switches the application symlink. The previous checkout, including its untracked operational files, is retained. On the first run, `.env` and `storage` move into `/var/www/mobilitycloud-shared`; subsequent releases use those same paths.

The script installs the dedicated `exports` Supervisor worker before reopening the application. The default worker continues processing other work. The database queue's `retry_after` must remain above the 900-second archive timeout (current setting: 1200 seconds). Do not enable `exports` on a different host without installing its worker.

Failures before activation leave the current release intact. Failures after activation return the symlink to the previous release and reopen it only after Laravel optimization and PHP-FPM reload succeed. Pending migrations are refused by default. Future, explicitly reviewed, backwards-compatible migrations can use `ALLOW_ADDITIVE_MIGRATIONS=1`; if a migration starts and deployment fails, maintenance remains enabled for operator review. Database migrations are never automatically reversed.

Run the first conversion during a maintenance window: the original directory must be renamed once before it becomes a symlink. Keep at least the current and previous release. Remove older releases only after verifying none is the target of `/var/www/mobilitycloud` or contains the shared data. The script intentionally does not automatically delete releases or untracked files.

## Server configuration (one-time setup)

1. Back up the current Nginx server configuration and PHP pool settings.
2. Install `nginx-performance.conf` outside the document root. Include it **inside the mobilitycloud.eu TLS server block**, replacing the existing `client_max_body_size 16M;` directive (do not leave both). Run `nginx -t` before reloading. The internal archive location must never be a public alias.
3. Install `php-performance.ini` in both `/etc/php/8.3/fpm/conf.d/` and `/etc/php/8.3/cli/conf.d/`. This permits the existing 50 MiB upload contract and 60 MiB upload batches within a 64 MiB request. Test with `php-fpm8.3 -t` before reloading PHP-FPM.
4. Add `php-fpm-observability.conf` settings to the existing `www` pool and validate again. Keep the existing `pm.max_children` until process RSS and latency justify a change.
5. After the Nginx internal location has been tested, set `MOBILITYCLOUD_FINAL_ARCHIVE_ACCELERATED_DOWNLOADS=true` in the shared environment. Until then, downloads use PHP's private binary-file response with range support. Set `MOBILITYCLOUD_PERFORMANCE_LOG=true` to record route-template latency/status/buffered-response size and peak PHP memory. These records omit request bodies, query strings and route token values; log retention is 14 days.
6. Rebuild Laravel's configuration cache and restart the queue workers after environment changes.

## Verification

- Confirm `/up`, login and existing project modules work, and both Supervisor workers are RUNNING.
- Generate an archive from a controlled project: `INDEX.html`, JSON, CSV, generated PDFs, selected originals and signatures must be present. Change project data, prepare a fresh archive and confirm a new download URL and updated content.
- Confirm direct access to `/_protected_archives/...` returns 404. Authenticated authorized downloads should succeed; another account or another project must not gain access. The app must keep `Cache-Control: private, no-store`.
- Confirm CSS/JS responses use gzip and static expiry. Upload a valid 20 MiB and 50 MiB file to the relevant existing upload fields; verify a batch over 60 MiB is rejected before transmission.
- Inspect `storage/logs/performance-*.log`, PHP-FPM slow logs and queue failures during ordinary traffic. HTTP timings exclude Nginx's subsequent file transfer; archive job logs separately record duration and ZIP size. Shared ZIP files expire after 24 hours and are purged hourly; abandoned temporary archive files are purged after three hours. Neither is included in storage backups.

## Manual rollback

Drain both workers and enter maintenance. Atomically point `/var/www/mobilitycloud` at the previous release, rebuild its Laravel caches, reload PHP-FPM, then reopen and start workers. If database migrations were attempted, assess compatibility before reopening. Keep the new export worker available while any jobs remain in the `exports` queue; the job class exists in the previous application version as well.

Server configuration changes have their own backups and should be restored separately when relevant. Do not delete the shared `.env` or `storage` directory during rollback. Backup/restore scripts that previously derived `storage` through the application path continue to resolve through the shared symlink; verify scheduled backups after the first conversion.
