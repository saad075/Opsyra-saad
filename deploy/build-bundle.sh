#!/usr/bin/env bash
#
# Builds the deployable bundle from a clean checkout — the CI pipeline runs
# this; you can too. Everything is built here, so the server needs only PHP
# (no Composer, no Node):
#
#   build/bundle/
#     us/          country app: PHP code + vendor/ + the built Vue app in public/
#     central/     Opsyra Central: PHP code + vendor/ + built pages in public/build
#     deploy.sh    what the server runs
#     .cpanel.yml  tells cPanel to run deploy.sh
#     BUILD        which commit this is
#
# Only files tracked by git are copied, so local .env files and secrets can
# never end up in a bundle.
#
# Settings (environment variables):
#   VITE_CENTRAL_URL   the website address the app links back to
#                      (default https://opsyra.brainstormertech.com)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/build/bundle"
export VITE_CENTRAL_URL="${VITE_CENTRAL_URL:-https://opsyra.brainstormertech.com}"

log() { printf '\n==> %s\n' "$*"; }

cd "$ROOT"
rm -rf "$OUT"
mkdir -p "$OUT/us" "$OUT/central"

# Tracked files only (no .env, no local node_modules/vendor, no _reference).
copy_tracked() { # <path in repo> <destination> <exclude regex>
  git ls-files -z -- "$1" | grep -zvE "$3" | while IFS= read -r -d '' file; do
    rel="${file#"$1"/}"; [ "$1" = "." ] && rel="$file"
    mkdir -p "$2/$(dirname "$rel")"
    cp -p "$file" "$2/$rel"
  done
}

log "Country app (us): PHP code"
copy_tracked . "$OUT/us" '^(central|frontend|tests|docs|deploy|docker|Design|\.github)/|^(phpunit\.xml|package\.json|package-lock\.json|vite\.config\.js|docker-compose\.yml)$'
(cd "$OUT/us" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress)

log "Country app (us): the Vue app (links back to $VITE_CENTRAL_URL)"
(cd frontend && npm ci --no-audit --no-fund && npm run build)
cp -R frontend/dist/. "$OUT/us/public/"
mv "$OUT/us/public/index.html" "$OUT/us/public/app.html"   # public/.htaccess serves it for every app page

log "Opsyra Central"
copy_tracked central "$OUT/central" '^central/tests/|^central/phpunit\.xml$'
(cd "$OUT/central" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress)
(cd "$OUT/central" && npm ci --no-audit --no-fund && npm run build && rm -rf node_modules)

log "Deploy script"
cp deploy/deploy.sh deploy/.cpanel.yml "$OUT/"
chmod +x "$OUT/deploy.sh"
git log -1 --format='%H%n%cI%n%s' > "$OUT/BUILD"

log "Bundle ready: $OUT ($(du -sh "$OUT" | cut -f1))"
