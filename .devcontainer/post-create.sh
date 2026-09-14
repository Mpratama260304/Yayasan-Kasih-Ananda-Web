#!/usr/bin/env bash
# Runs once, right after the dev container is created.
# Keep it fast and non-destructive: it only prepares local files.
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Preparing Yayasan Kasih Ananda development workspace"

if [[ ! -f .env ]]; then
	cp .env.example .env
	# Generate throwaway local database credentials so no default password
	# is ever shared between machines or committed anywhere.
	rand() { head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20; }
	sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(rand)|" .env
	sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(rand)|" .env
	echo "    created .env with generated local database credentials"
else
	echo "    .env already exists, leaving it untouched"
fi

chmod +x scripts/*.sh 2>/dev/null || true

if command -v composer >/dev/null 2>&1 && [[ -f composer.json ]]; then
	composer install --no-interaction --no-progress || \
		echo "    composer install failed (non-fatal) — run it manually later"
fi

cat <<'MSG'

Workspace ready.

  ./scripts/start.sh       start Docker services
  ./scripts/bootstrap.sh   extract WordPress + install the site
  ./scripts/seed.sh        add demo content (development only)
  ./scripts/check.sh       run code + site checks

MSG
