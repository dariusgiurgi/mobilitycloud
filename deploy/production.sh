#!/usr/bin/env bash
# Run as root, with EXPECTED_SHA set to the revision that passed the quality gate.
set -Eeuo pipefail
umask 027
readonly APP=/var/www/mobilitycloud
readonly RELEASES=/var/www/mobilitycloud-releases
readonly SHARED=/var/www/mobilitycloud-shared
[[ ${EXPECTED_SHA:-} =~ ^[a-f0-9]{40}$ ]] || { echo 'EXPECTED_SHA must be a full Git SHA.'; exit 1; }
exec 9>/var/lock/mobilitycloud-deploy.lock
flock -n 9 || { echo 'Another deploy is running.'; exit 1; }
as_deploy() { runuser -u deploy -- "$@"; }
prepare_runtime_permissions() {
    install -d -m 2770 -o deploy -g www-data "$SHARED/storage/framework" "$SHARED/storage/logs"
    chgrp -R www-data "$SHARED/storage/framework" "$SHARED/storage/logs"
    chmod -R g+rwX "$SHARED/storage/framework" "$SHARED/storage/logs"
    find "$SHARED/storage/framework" "$SHARED/storage/logs" -type d -exec chmod g+s {} +
    setfacl -R -m u:deploy:rwX,u:www-data:rwX "$SHARED/storage/framework" "$SHARED/storage/logs"
    find "$SHARED/storage/framework" "$SHARED/storage/logs" -type d -exec setfacl -m d:u:deploy:rwx,d:u:www-data:rwx {} +
}

command -v setfacl >/dev/null
cd "$APP"
# Git reports tracked storage placeholders as deleted across the shared-directory symlink.
# Validate the expected target before excluding mutable shared storage from checkout checks.
checkout_paths=(.)
if [[ -L "$APP/storage" ]]; then
    test "$(readlink -f "$APP/storage")" = "$SHARED/storage"
    checkout_paths+=(':(exclude)storage')
fi
as_deploy git diff --quiet -- "${checkout_paths[@]}" && as_deploy git diff --cached --quiet -- "${checkout_paths[@]}" || { echo 'Tracked production files have local changes.'; exit 1; }
as_deploy git fetch --prune origin main
test "$(as_deploy git rev-parse origin/main)" = "$EXPECTED_SHA" || { echo 'Verified revision is no longer main.'; exit 1; }
readonly RELEASE="$RELEASES/$(date -u +%Y%m%dT%H%M%SZ)-${EXPECTED_SHA:0:12}"
previous=$(readlink -f "$APP")
maintenance=0
migrations_started=0
switched=0
finished=0

activate() {
    local switch_dir
    switch_dir=$(mktemp -d "${APP}.switch.XXXXXX")
    ln -s "$1" "$switch_dir/next"
    mv -Tf "$switch_dir/next" "$APP"
    rmdir "$switch_dir"
}
recover() {
    result=$?
    trap - EXIT
    if (( finished == 0 )); then
        echo "Deployment failed; previous release: $previous"
        if (( maintenance == 1 )); then
            (cd "$APP" && as_deploy php artisan down --retry=60 --no-interaction) || true
        fi
        if (( switched == 1 )); then
            supervisorctl stop 'mobilitycloud-worker:*' || true
            supervisorctl stop 'mobilitycloud-exports:*' || true
            if ! activate "$previous"; then
                echo 'Could not restore the previous symlink: maintenance remains enabled.'
                exit "$result"
            fi
        fi
        if (( maintenance == 1 && migrations_started == 0 )); then
            # Only reopen the old, intact release. Failed schema changes stay in maintenance.
            cd "$APP"
            if as_deploy php artisan optimize --no-interaction && systemctl reload php8.3-fpm; then
                as_deploy php artisan up --no-interaction
                supervisorctl start 'mobilitycloud-worker:*' || true
                supervisorctl start 'mobilitycloud-exports:*' || true
            else
                echo 'Rollback validation failed: maintenance stays enabled.'
            fi
        elif (( migrations_started == 1 )); then
            echo 'Schema migration started: maintenance stays enabled for operator review. No automatic database rollback.'
        fi
    fi
    exit "$result"
}
trap recover EXIT

install -d -m 2750 -o deploy -g www-data "$RELEASES"
as_deploy git clone --quiet --no-checkout https://github.com/dariusgiurgi/mobilitycloud.git "$RELEASE"
as_deploy git -C "$RELEASE" checkout --quiet --detach "$EXPECTED_SHA"
install -m 640 -o deploy -g www-data "$APP/.env" "$RELEASE/.env"
cd "$RELEASE"
# Build in isolation while the previous release continues serving requests.
as_deploy composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
as_deploy npm ci --no-audit --no-fund
as_deploy npm run build
as_deploy php artisan migrate:status --no-interaction > "$RELEASE/migration-status.txt"
if grep -q Pending "$RELEASE/migration-status.txt" && [[ ${ALLOW_ADDITIVE_MIGRATIONS:-0} != 1 ]]; then
    echo 'Pending migrations require review for backwards compatibility before deployment.'
    exit 1
fi
as_deploy php artisan mobilitycloud:prelaunch-audit --no-interaction

cd "$APP"
as_deploy php artisan mobilitycloud:backup --skip-storage --no-retention --no-interaction
as_deploy php artisan down --retry=60 --no-interaction
maintenance=1
supervisorctl stop 'mobilitycloud-worker:*'
if supervisorctl status 'mobilitycloud-exports:*' >/dev/null 2>&1; then
    supervisorctl stop 'mobilitycloud-exports:*'
fi

# One-time conversion keeps all original untracked files in a recoverable legacy release.
if [[ ! -L "$APP" ]]; then
    install -d -m 2750 -o deploy -g www-data "$SHARED"
    for item in storage .env; do
        if [[ -L "$APP/$item" ]]; then
            test "$(readlink -f "$APP/$item")" = "$SHARED/$item"
        else
            test ! -e "$SHARED/$item"
            mv "$APP/$item" "$SHARED/$item"
            ln -s "$SHARED/$item" "$APP/$item"
        fi
    done
    previous="$RELEASES/legacy-$(date -u +%Y%m%dT%H%M%SZ)"
    mv "$APP" "$previous"
    ln -s "$previous" "$APP"
fi
rm -rf "$RELEASE/storage"
rm "$RELEASE/.env"
ln -s "$SHARED/storage" "$RELEASE/storage"
ln -s "$SHARED/.env" "$RELEASE/.env"
chown -R deploy:www-data "$RELEASE"
chmod -R g+rX "$RELEASE"
chmod -R g+w "$RELEASE/bootstrap/cache"
prepare_runtime_permissions
cd "$RELEASE"
if grep -q Pending migration-status.txt; then
    migrations_started=1
    as_deploy php artisan migrate --force --no-interaction
fi
as_deploy php artisan optimize --no-interaction
prepare_runtime_permissions
activate "$RELEASE"
switched=1
systemctl reload php8.3-fpm
install -m 644 "$RELEASE/deploy/mobilitycloud-exports.conf" /etc/supervisor/conf.d/mobilitycloud-exports.conf
supervisorctl reread
supervisorctl update
as_deploy php artisan queue:restart --no-interaction
as_deploy php artisan up --no-interaction
curl --fail --silent --show-error --retry 3 --retry-delay 2 --resolve mobilitycloud.eu:443:127.0.0.1 https://mobilitycloud.eu/up >/dev/null
test "$(curl --fail --silent --show-error --retry 3 --retry-delay 2 --output /dev/null --write-out '%{http_code}' --resolve mobilitycloud.eu:443:127.0.0.1 https://mobilitycloud.eu/app/login)" = 200
supervisorctl start 'mobilitycloud-worker:*' || supervisorctl status 'mobilitycloud-worker:*' | grep -q RUNNING
supervisorctl start 'mobilitycloud-exports:*' || supervisorctl status 'mobilitycloud-exports:*' | grep -q RUNNING
finished=1
echo "Activated $EXPECTED_SHA; previous release retained at $previous"
