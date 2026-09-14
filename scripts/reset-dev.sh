#!/usr/bin/env bash
# ---------------------------------------------------------------
# Destroy and rebuild the local development site.
#
# DELETES the development database and uploads. It never touches
# anything outside this workspace, and it is blocked outright when
# WP_ENVIRONMENT_TYPE is production.
#
#   ./scripts/reset-dev.sh
# ---------------------------------------------------------------
# shellcheck source=scripts/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

load_env
require_docker

if [[ "${WP_ENVIRONMENT_TYPE:-local}" == "production" ]]; then
	die "refusing to reset a production environment"
fi

cat <<EOF
${C_YELLOW}${C_BOLD}This will permanently delete:${C_RESET}
  - the local development database (all posts, pages, settings)
  - ./wp/wp-content/uploads (all locally uploaded media)
  - downloaded third-party plugins in ./wp/wp-content/plugins

It will NOT touch your custom theme or plugin source in wp-content/.
EOF

read -r -p "Type 'reset' to continue: " CONFIRM
[[ "$CONFIRM" == "reset" ]] || die "aborted"

step "Stopping containers and removing volumes"
dc down -v --remove-orphans

step "Clearing runtime files"
rm -rf "$PROJECT_ROOT/wp/wp-content/uploads" \
       "$PROJECT_ROOT/wp/wp-content/cache" \
       "$PROJECT_ROOT/wp/wp-content/debug.log" \
       "$PROJECT_ROOT/wp/wp-config.php"

find "$PROJECT_ROOT/wp/wp-content/plugins" -mindepth 1 -maxdepth 1 \
	! -name 'yka-core' -exec rm -rf {} + 2>/dev/null || true

ok "development environment cleared"

cat <<EOF

Rebuild with:
  ${C_BOLD}./scripts/start.sh && ./scripts/bootstrap.sh && ./scripts/seed.sh${C_RESET}
EOF
