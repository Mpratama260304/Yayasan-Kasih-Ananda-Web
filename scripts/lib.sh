#!/usr/bin/env bash
# ---------------------------------------------------------------
# Shared helpers for the ./scripts/* commands.
# Sourced, never executed directly.
# ---------------------------------------------------------------

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

# --- pretty output -------------------------------------------------
if [[ -t 1 ]]; then
	C_RESET=$'\033[0m'; C_BOLD=$'\033[1m'; C_DIM=$'\033[2m'
	C_GREEN=$'\033[32m'; C_YELLOW=$'\033[33m'; C_RED=$'\033[31m'; C_BLUE=$'\033[36m'
else
	C_RESET=""; C_BOLD=""; C_DIM=""; C_GREEN=""; C_YELLOW=""; C_RED=""; C_BLUE=""
fi

say()  { printf '%s==>%s %s\n' "$C_BLUE$C_BOLD" "$C_RESET" "$*"; }
ok()   { printf '%s  ok%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn() { printf '%s  !!%s %s\n' "$C_YELLOW" "$C_RESET" "$*" >&2; }
die()  { printf '%s ERR%s %s\n' "$C_RED$C_BOLD" "$C_RESET" "$*" >&2; exit 1; }
step() { printf '\n%s%s%s\n' "$C_BOLD" "$*" "$C_RESET"; }

# --- environment ---------------------------------------------------
load_env() {
	if [[ ! -f "$PROJECT_ROOT/.env" ]]; then
		warn ".env not found — creating it from .env.example"
		cp "$PROJECT_ROOT/.env.example" "$PROJECT_ROOT/.env"
		local p1 p2
		p1="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
		p2="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
		sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${p1}|" "$PROJECT_ROOT/.env"
		sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=${p2}|" "$PROJECT_ROOT/.env"
	fi
	set -a
	# shellcheck disable=SC1091
	source "$PROJECT_ROOT/.env"
	set +a

	WP_PORT="${WP_PORT:-8080}"
	PMA_PORT="${PMA_PORT:-8081}"
	MAILPIT_UI_PORT="${MAILPIT_UI_PORT:-8025}"
}

# Public URL of the development site: Codespaces forwarded host when
# available, plain localhost otherwise.
site_url() {
	if [[ -n "${WP_HOME:-}" ]]; then
		printf '%s' "$WP_HOME"
	elif [[ -n "${CODESPACE_NAME:-}" && -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]]; then
		printf 'https://%s-%s.%s' "$CODESPACE_NAME" "$WP_PORT" "$GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN"
	else
		printf 'http://localhost:%s' "$WP_PORT"
	fi
}

tool_url() {
	local port="$1"
	if [[ -n "${CODESPACE_NAME:-}" && -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]]; then
		printf 'https://%s-%s.%s' "$CODESPACE_NAME" "$port" "$GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN"
	else
		printf 'http://localhost:%s' "$port"
	fi
}

# Origin to run automated checks against. Codespaces forwarded URLs sit
# behind an authenticating proxy, so tests talk to the container directly.
local_url() {
	printf 'http://localhost:%s' "$WP_PORT"
}

# --- docker helpers ------------------------------------------------
dc() { docker compose "$@"; }

require_docker() {
	command -v docker >/dev/null 2>&1 || die "docker is not available on PATH"
	docker info >/dev/null 2>&1 || die "the Docker daemon is not reachable"
}

# Run WP-CLI inside the project container.
#
# `exec` reuses the long-running wpcli container, which is markedly faster
# than spawning one per command. It falls back to `run` when the container
# is not up yet.
wp() {
	if docker compose ps --status running --services 2>/dev/null | grep -qx wpcli; then
		dc exec -T --user 33:33 -e WP_CLI_CACHE_DIR=/tmp/wp-cli-cache \
			wpcli wp --path=/var/www/html "$@"
	else
		dc run --rm -T --user 33:33 \
			-e WP_CLI_CACHE_DIR=/tmp/wp-cli-cache \
			--entrypoint wp \
			wpcli --path=/var/www/html "$@"
	fi
}

# Same, but never aborts the calling script on failure.
wp_soft() { wp "$@" || return $?; }

wp_installed() { wp core is-installed >/dev/null 2>&1; }

# Set an option, tolerating WP-CLI's error when the value is unchanged.
# Without this the whole bootstrap aborts on a second run.
wp_option() {
	local name="$1" value="$2"
	wp option update "$name" "$value" >/dev/null 2>&1 || true
}

wait_for_db() {
	say "Waiting for the database"
	local i
	for i in $(seq 1 60); do
		if dc exec -T db healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then
			ok "database is ready"
			return 0
		fi
		sleep 2
	done
	die "database did not become ready in time — check: docker compose logs db"
}

wait_for_http() {
	local url="$1" label="${2:-site}" i
	say "Waiting for $label"
	for i in $(seq 1 60); do
		if curl -ksSf -o /dev/null "$url" 2>/dev/null; then
			ok "$label responds"
			return 0
		fi
		sleep 2
	done
	warn "$label did not respond in time"
	return 1
}
