#!/usr/bin/env bash
#
# Opsyra deploy — run on the server by cPanel's Git deployment
# (.cpanel.yml), from the root of the built bundle the CI pipeline produced:
#
#   bundle/
#     us/        the country app (API + built Vue app), vendor included
#     central/   Opsyra Central, vendor and built pages included
#     deploy.sh  this script
#
# Each app is deployed as a *release*:
#
#   ~/opsyra/<app>/releases/<time>/   one folder per deploy
#   ~/opsyra/<app>/shared/.env        settings (you create it once)
#   ~/opsyra/<app>/shared/storage/    logs, uploads, caches — kept across deploys
#   ~/opsyra/<app>/current            link to the live release
#
# The subdomain's document root is ~/opsyra/<app>/current/public, so the
# switch to a new release is one atomic link change. Roll back with:
#   ln -sfn ~/opsyra/<app>/releases/<previous> ~/opsyra/<app>/current
#
# Settings (optional) in ~/.opsyra-deploy.env, e.g.:
#   PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php
#   OPSYRA_HOME=$HOME/opsyra
#   OPSYRA_APPS="us central"
#
set -euo pipefail

BUNDLE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[ -f "$HOME/.opsyra-deploy.env" ] && . "$HOME/.opsyra-deploy.env"

PHP_BIN="${PHP_BIN:-$(command -v php)}"
OPSYRA_HOME="${OPSYRA_HOME:-$HOME/opsyra}"
OPSYRA_APPS="${OPSYRA_APPS:-us central}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
STAMP="$(date -u +%Y%m%d%H%M%S)"

log() { printf '\n==> %s\n' "$*"; }
fail() { printf '\n!!! %s\n' "$*" >&2; exit 1; }

"$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || fail "PHP 8.2 or newer is needed ($("$PHP_BIN" -r 'echo PHP_VERSION;') found). Set PHP_BIN in ~/.opsyra-deploy.env."
"$PHP_BIN" -m | grep -qi '^pdo_pgsql$' || fail "PHP's pdo_pgsql extension is missing. Enable it in cPanel → Select PHP Version (or MultiPHP)."

deploy_app() {
  local app="$1"
  local base="$OPSYRA_HOME/$app"
  local release="$base/releases/$STAMP"
  local artisan=("$PHP_BIN" "$release/artisan")

  log "Deploying $app → $release"

  [ -d "$BUNDLE/$app" ] || fail "The bundle has no '$app' folder."
  [ -f "$base/shared/.env" ] || fail "Missing $base/shared/.env — create it from deploy/$app.env.example first (see docs/deployment.md)."

  mkdir -p "$base/releases" \
    "$base/shared/storage/app/public" \
    "$base/shared/storage/framework/cache/data" \
    "$base/shared/storage/framework/sessions" \
    "$base/shared/storage/framework/views" \
    "$base/shared/storage/logs"

  cp -R "$BUNDLE/$app" "$release"
  rm -rf "$release/storage"
  ln -s "$base/shared/storage" "$release/storage"
  ln -s "$base/shared/.env" "$release/.env"
  mkdir -p "$release/bootstrap/cache"
  rm -f "$release/bootstrap/cache/"*.php

  # Safety before touching the database (country apps: company separation).
  if [ "$app" != "central" ]; then
    "${artisan[@]}" opsyra:preflight || fail "Preflight failed for $app — nothing was changed. The live site is still the previous release."
  fi

  "${artisan[@]}" migrate --force

  if [ "$app" != "central" ]; then
    "${artisan[@]}" opsyra:preflight --tables || fail "After migrating, a company table isn't protected. The previous release is still live; fix before switching."
  fi

  "${artisan[@]}" storage:link --force >/dev/null 2>&1 || true
  "${artisan[@]}" config:cache
  "${artisan[@]}" route:cache
  "${artisan[@]}" view:cache
  "${artisan[@]}" event:cache

  # Go live: one atomic switch of the link the web server follows.
  ln -sfn "$release" "$base/current.tmp"
  mv -Tf "$base/current.tmp" "$base/current"

  # Workers pick up the new code on their next job.
  "$PHP_BIN" "$base/current/artisan" queue:restart >/dev/null 2>&1 || true

  # Keep the last few releases for rollback.
  ls -1dt "$base/releases/"*/ | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf

  log "$app is live: $(readlink "$base/current")"
}

for app in $OPSYRA_APPS; do
  deploy_app "$app"
done

log "Done."
