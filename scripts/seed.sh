#!/usr/bin/env bash
# ---------------------------------------------------------------
# Development-only demo content.
#
# Everything created here is prefixed [DEMO] so it can never be
# mistaken for real information about Yayasan Kasih Ananda.
#
#   ./scripts/seed.sh            add demo content
#   ./scripts/seed.sh --remove   delete every [DEMO] item again
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env
require_docker

if [[ "${WP_ENVIRONMENT_TYPE:-local}" == "production" ]]; then
	die "refusing to seed demo content into a production environment"
fi

wp_installed || die "WordPress is not installed yet — run ./scripts/bootstrap.sh first"

if [[ "${1:-}" == "--remove" ]]; then
	step "Removing demo content"
	wp eval-file /scripts/php/seed.php -- --remove
	wp cache flush >/dev/null 2>&1 || true
	ok "demo content removed"
	exit 0
fi

step "Seeding demo content"
wp eval-file /scripts/php/seed.php
wp cache flush >/dev/null 2>&1 || true

cat <<EOF

${C_BOLD}${C_GREEN}Demo content created.${C_RESET}

  $(site_url)

Every demo article title starts with [DEMO].
Remove it all before going live:  ${C_BOLD}./scripts/seed.sh --remove${C_RESET}
EOF
