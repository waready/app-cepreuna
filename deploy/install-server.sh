#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${APP_ROOT:-/var/www/app-cepreuna}"
REPO_DIR="${REPO_DIR:-/var/repositories/app-cepreuna.git}"
EXISTING_RELEASE="${EXISTING_RELEASE:-}"
SHARED_DIR="$APP_ROOT/shared"

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

chmod -R ug+rwX "$APP_ROOT" "$REPO_DIR"

cat <<EOF
Server deployment repository prepared.

Repository: $REPO_DIR
Application: $APP_ROOT
Nginx root must be: $APP_ROOT/current/public

Before the first push, confirm these files exist:
  $SHARED_DIR/.env
  $SHARED_DIR/deploy.env (optional)

No database migrations were executed.
EOF
