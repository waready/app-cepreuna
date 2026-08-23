#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${APP_ROOT:-/var/www/app-cepreuna}"
REPO_DIR="${REPO_DIR:-/var/repositories/app-cepreuna.git}"
EXISTING_RELEASE="${EXISTING_RELEASE:-}"
DEPLOY_USER="${DEPLOY_USER:-${SUDO_USER:-$(id -un)}}"
WEB_GROUP="${WEB_GROUP:-www-data}"
SHARED_DIR="$APP_ROOT/shared"

id "$DEPLOY_USER" >/dev/null 2>&1 || {
    echo "Deployment user does not exist: $DEPLOY_USER"
    exit 1
}
getent group "$WEB_GROUP" >/dev/null 2>&1 || {
    echo "Web group does not exist: $WEB_GROUP"
    exit 1
}

mkdir -p "$REPO_DIR" "$APP_ROOT/releases"
mkdir -p \
    "$SHARED_DIR/storage/app/public" \
    "$SHARED_DIR/storage/framework/cache/data" \
    "$SHARED_DIR/storage/framework/sessions" \
    "$SHARED_DIR/storage/framework/views" \
    "$SHARED_DIR/storage/logs"

if [[ ! -d "$REPO_DIR/objects" ]]; then
    git init --bare "$REPO_DIR"
fi

install -m 0755 "$SCRIPT_DIR/post-receive" "$REPO_DIR/hooks/post-receive"

if [[ -n "$EXISTING_RELEASE" && -f "$EXISTING_RELEASE/.env" && ! -f "$SHARED_DIR/.env" ]]; then
    cp -p "$EXISTING_RELEASE/.env" "$SHARED_DIR/.env"
fi

if [[ "$(id -u)" -eq 0 ]]; then
    chown "$DEPLOY_USER:$WEB_GROUP" "$APP_ROOT" "$APP_ROOT/releases" "$SHARED_DIR"
    chown -R "$DEPLOY_USER:$WEB_GROUP" "$REPO_DIR" "$SHARED_DIR/storage"
    if [[ -f "$SHARED_DIR/.env" ]]; then
        chown "$DEPLOY_USER:$WEB_GROUP" "$SHARED_DIR/.env"
        chmod 0640 "$SHARED_DIR/.env"
    fi
fi

chmod 2775 "$APP_ROOT" "$APP_ROOT/releases" "$SHARED_DIR" "$SHARED_DIR/storage" "$REPO_DIR"
chmod -R ug+rwX "$REPO_DIR" "$SHARED_DIR/storage"

cat <<EOF
Server deployment repository prepared.

Repository: $REPO_DIR
Application: $APP_ROOT
Deployment user: $DEPLOY_USER
Nginx root must be: $APP_ROOT/current/public

Before the first push, confirm these files exist:
  $SHARED_DIR/.env
  $SHARED_DIR/deploy.env (optional)

No database migrations were executed.
EOF
